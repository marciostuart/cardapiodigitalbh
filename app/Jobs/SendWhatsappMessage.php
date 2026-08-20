<?php

namespace App\Jobs;

use App\Models\OutboundMessage;
use App\Services\EvolutionApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWhatsappMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 45;

    public function __construct(public int $messageId) {}

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(EvolutionApi $api): void
    {
        $message = OutboundMessage::with('tenant.evolutionInstance')->findOrFail($this->messageId);
        if ($message->status === 'sent') return;
        $instance = $message->tenant->evolutionInstance;
        if (! $instance || $instance->status !== 'connected') {
            $message->update(['status' => 'failed', 'last_error' => 'Instância WhatsApp não conectada.']);
            return;
        }
        $message->increment('attempts');
        try {
            $api->sendText($instance->instance_name, $message->recipient, $message->body);
            $message->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $error) {
            $message->update(['status' => 'retrying', 'last_error' => mb_substr($error->getMessage(), 0, 1000)]);
            throw $error;
        }
    }

    public function failed(?Throwable $error): void
    {
        OutboundMessage::whereKey($this->messageId)->update(['status' => 'failed', 'last_error' => mb_substr($error?->getMessage() ?? 'Falha definitiva no envio.', 0, 1000)]);
    }
}
