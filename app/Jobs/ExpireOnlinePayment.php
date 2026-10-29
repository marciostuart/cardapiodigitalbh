<?php

namespace App\Jobs;

use App\Models\Coupon;
use App\Models\AuditLog;
use App\Models\MercadoPagoConnection;
use App\Models\OnlinePayment;
use App\Services\InventoryService;
use App\Services\MercadoPagoService;
use App\Services\WhatsappNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireOnlinePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public function __construct(public int $onlinePaymentId) {}

    public function backoff(): array
    {
        return [30, 120, 300, 900, 1800];
    }

    public function handle(MercadoPagoService $mercadoPago): void
    {
        $payment = OnlinePayment::find($this->onlinePaymentId);
        $expirableStatuses = ['pending', 'failed', 'rejected', 'cancelled'];
        if (! $payment || ! in_array($payment->status, $expirableStatuses, true)) {
            return;
        }
        if ($payment->expires_at?->isFuture()) {
            self::dispatch($payment->id)->delay($payment->expires_at)->onQueue('payments');

            return;
        }

        $connection = MercadoPagoConnection::where('tenant_id', $payment->tenant_id)->firstOrFail();
        $latest = $mercadoPago->searchPayment($payment->external_reference, $connection);
        if ($latest && isset($latest['id'])) {
            SyncMercadoPagoPayment::dispatchSync((string) $latest['id'], $connection->seller_id);
            $payment->refresh();
            if (! in_array($payment->status, $expirableStatuses, true)) {
                return;
            }
        }

        DB::transaction(function () use ($expirableStatuses): void {
            $payment = OnlinePayment::lockForUpdate()->find($this->onlinePaymentId);
            if (! $payment || ! in_array($payment->status, $expirableStatuses, true) || $payment->expires_at?->isFuture()) {
                return;
            }

            $order = $payment->order()->lockForUpdate()->first();
            if (! $order) {
                $payment->update(['status' => 'expired']);

                return;
            }

            $payment->update(['status' => 'expired']);
            if ($order->status === 'cancelled') {
                $order->update(['payment_status' => 'expired']);

                return;
            }

            $order->update(['status' => 'cancelled', 'payment_status' => 'expired']);
            app(InventoryService::class)->release($order);
            if ($order->coupon_code) {
                Coupon::where('venue_id', $order->venue_id)
                    ->where('code', $order->coupon_code)
                    ->where('used_count', '>', 0)
                    ->decrement('used_count');
            }
            DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), 'order_cancelled'));
        });
    }

    public function failed(?Throwable $error): void
    {
        $payment = OnlinePayment::with('order')->find($this->onlinePaymentId);
        if (! $payment || ! in_array($payment->status, ['pending', 'failed', 'rejected', 'cancelled'], true)) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $current = OnlinePayment::lockForUpdate()->find($payment->id);
            if (! $current || ! in_array($current->status, ['pending', 'failed', 'rejected', 'cancelled'], true)) {
                return;
            }
            $order = $current->order()->lockForUpdate()->first();
            $current->update(['status' => 'review_required']);
            $order?->update(['payment_status' => 'review_required']);
            AuditLog::create([
                'tenant_id' => $current->tenant_id,
                'action' => 'payment.expiration_check_failed',
                'subject_type' => OnlinePayment::class,
                'subject_id' => $current->id,
                'metadata' => ['order_id' => $order?->id],
            ]);
        });
        report($error);
    }
}
