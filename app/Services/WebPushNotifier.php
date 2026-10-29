<?php

namespace App\Services;

use App\Jobs\SendWebPushMessage;
use App\Models\OutboundMessage;
use App\Models\Order;
use App\Models\User;
use Throwable;

class WebPushNotifier
{
    public function queueNewOrder(Order $order): int
    {
        if (! config('services.webpush.public_key') || ! config('services.webpush.private_key')) {
            return 0;
        }

        $order->loadMissing('venue');
        $tenantId = (int) $order->venue->tenant_id;
        $queued = 0;

        try {
            User::query()
                ->where('tenant_id', $tenantId)
                ->where('active', true)
                ->whereIn('role', ['owner', 'waiter', 'kitchen', 'cashier'])
                ->with('pushSubscriptions')
                ->get()
                ->each(function (User $user) use ($order, $tenantId, &$queued): void {
                $destination = match ($user->role) {
                    'owner' => '/painel/pedidos/'.$order->id,
                    'waiter' => '/operacao',
                    'kitchen' => '/cozinha',
                    'cashier' => '/caixa',
                    default => '/',
                };
                $payload = json_encode([
                    'title' => 'Novo pedido recebido',
                    'body' => 'Acesse o painel para acompanhar o pedido.',
                    'url' => $destination,
                    'tag' => 'pedido-'.$order->id,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                foreach ($user->pushSubscriptions as $subscription) {
                    $key = hash('sha256', implode('|', [$tenantId, $order->id, 'new_order', $subscription->endpoint_hash]));
                    $message = OutboundMessage::firstOrCreate(['idempotency_key' => $key], [
                        'tenant_id' => $tenantId,
                        'order_id' => $order->id,
                        'channel' => 'push',
                        'event' => 'new_order',
                        'recipient' => $subscription->endpoint,
                        'body' => $payload,
                    ]);

                    if ($message->wasRecentlyCreated) {
                        SendWebPushMessage::dispatch($message->id);
                        $queued++;
                    }
                }
                });
        } catch (Throwable $error) {
            report($error);
        }

        return $queued;
    }
}
