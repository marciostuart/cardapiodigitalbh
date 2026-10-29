<?php

namespace App\Jobs;

use App\Models\OutboundMessage;
use App\Models\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendWebPushMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 45;

    public function __construct(public int $messageId) {}

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(): void
    {
        $message = OutboundMessage::whereKey($this->messageId)->where('channel', 'push')->firstOrFail();
        if ($message->status === 'sent') {
            return;
        }

        $subscription = PushSubscription::where('tenant_id', $message->tenant_id)
            ->where('endpoint_hash', hash('sha256', $message->recipient))
            ->first();
        if (! $subscription) {
            $message->update(['status' => 'failed', 'last_error' => 'Inscrição removida antes do envio.']);

            return;
        }

        $publicKey = (string) config('services.webpush.public_key');
        $privateKey = (string) config('services.webpush.private_key');
        $subject = (string) config('services.webpush.subject');
        if ($publicKey === '' || $privateKey === '' || $subject === '') {
            $message->update(['status' => 'failed', 'last_error' => 'Web Push não está configurado no ambiente.']);

            return;
        }

        $message->increment('attempts');

        try {
            $sender = new WebPush([
                'VAPID' => ['subject' => $subject, 'publicKey' => $publicKey, 'privateKey' => $privateKey],
            ], ['TTL' => 300, 'urgency' => 'normal']);
            $browserSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => ['p256dh' => $subscription->public_key, 'auth' => $subscription->auth_token],
            ]);
            $report = $sender->sendOneNotification($browserSubscription, $message->body);
            if ($report->isSuccess()) {
                $message->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                $subscription->update(['last_used_at' => now()]);

                return;
            }

            if ($report->isSubscriptionExpired()) {
                $subscription->delete();
                $message->update(['status' => 'failed', 'last_error' => 'O serviço de notificações encerrou esta inscrição.']);

                return;
            }

            $status = $report->getResponse()?->getStatusCode();
            $reason = $status === null ? 'Serviço push indisponível.' : 'Serviço push respondeu HTTP '.$status.'.';
            if ($status === null || $status === 429 || $status >= 500) {
                $message->update(['status' => 'retrying', 'last_error' => $reason]);
                throw new \RuntimeException('Falha temporária no envio de notificação push.');
            }

            $message->update(['status' => 'failed', 'last_error' => $reason]);
        } catch (Throwable $error) {
            $message->update(['status' => 'retrying', 'last_error' => 'Falha técnica no envio de notificação push.']);
            throw new \RuntimeException('Falha técnica no envio de notificação push.');
        }
    }

    public function failed(?Throwable $error): void
    {
        OutboundMessage::whereKey($this->messageId)->where('channel', 'push')->update([
            'status' => 'failed',
            'last_error' => 'Falha definitiva no envio de notificação push.',
        ]);
    }
}
