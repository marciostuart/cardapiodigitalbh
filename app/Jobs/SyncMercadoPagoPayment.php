<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\MercadoPagoConnection;
use App\Models\OnlinePayment;
use App\Services\MercadoPagoService;
use App\Services\WhatsappNotifier;
use App\Services\WebPushNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SyncMercadoPagoPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public function __construct(public string $providerPaymentId, public string $sellerId) {}

    public function backoff(): array
    {
        return [15, 60, 180, 600, 1800];
    }

    public function handle(MercadoPagoService $mercadoPago): void
    {
        $connection = MercadoPagoConnection::where('seller_id', $this->sellerId)->first();
        if (! $connection) {
            return;
        }

        $provider = $mercadoPago->getPayment($this->providerPaymentId, $connection);
        $externalReference = (string) ($provider['external_reference'] ?? '');
        $payment = OnlinePayment::where('tenant_id', $connection->tenant_id)
            ->where('external_reference', $externalReference)
            ->first();

        if (! $payment) {
            return;
        }

        if ((string) ($provider['collector_id'] ?? '') !== $connection->seller_id
            || strtoupper((string) ($provider['currency_id'] ?? '')) !== 'BRL'
            || abs((float) ($provider['transaction_amount'] ?? 0) - (float) $payment->amount) > 0.009) {
            throw new RuntimeException('O pagamento consultado não corresponde ao pedido local.');
        }

        $providerStatus = (string) ($provider['status'] ?? 'unknown');
        $mappedStatus = match ($providerStatus) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded', 'charged_back' => 'refunded',
            'pending', 'in_process', 'authorized' => 'pending',
            default => 'review_required',
        };

        DB::transaction(function () use ($payment, $provider, $providerStatus, $mappedStatus): void {
            $current = OnlinePayment::lockForUpdate()->findOrFail($payment->id);
            $order = $current->order()->lockForUpdate()->firstOrFail();
            $wasApproved = $current->status === 'approved';
            $paidAt = $provider['date_approved'] ?? null;

            if ($wasApproved && $mappedStatus !== 'refunded') {
                return;
            }

            if ($mappedStatus === 'approved' && $order->status === 'cancelled') {
                $current->update([
                    'provider_payment_id' => (string) $provider['id'],
                    'provider_status' => $providerStatus,
                    'status' => 'review_required',
                    'paid_at' => $paidAt,
                ]);
                $order->update(['payment_status' => 'review_required']);
                AuditLog::create([
                    'tenant_id' => $current->tenant_id,
                    'action' => 'payment.approved_after_order_cancellation',
                    'subject_type' => OnlinePayment::class,
                    'subject_id' => $current->id,
                    'metadata' => ['order_id' => $order->id, 'provider_payment_id' => (string) $provider['id']],
                ]);

                return;
            }

            $current->update([
                'provider_payment_id' => (string) $provider['id'],
                'provider_status' => $providerStatus,
                'status' => $mappedStatus,
                'paid_at' => $paidAt,
            ]);
            $orderStatus = $mappedStatus;
            if ($mappedStatus === 'refunded' && $order->status === 'pending') {
                $order->update(['status' => 'cancelled', 'payment_status' => $orderStatus]);
                app(\App\Services\InventoryService::class)->release($order);
                if ($order->coupon_code) {
                    Coupon::where('venue_id', $order->venue_id)->where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
                }
                DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), 'order_cancelled'));
            } else {
                $order->update(['payment_status' => $orderStatus]);
            }

            if ($mappedStatus === 'approved' && ! $wasApproved) {
                DB::afterCommit(function () use ($order): void {
                    $fresh = $order->fresh();
                    app(WhatsappNotifier::class)->queue($fresh, 'order_received');
                    app(WebPushNotifier::class)->queueNewOrder($fresh);
                });
            }
        });
    }

    public function failed(?Throwable $error): void
    {
        report($error);
    }
}
