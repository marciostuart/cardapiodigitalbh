<?php

namespace App\Services;

use App\Jobs\SendWhatsappMessage;
use App\Models\Order;
use App\Models\OutboundMessage;
use App\Models\WhatsappTemplate;

class WhatsappNotifier
{
    public function queue(Order $order, string $event): ?OutboundMessage
    {
        $order->loadMissing('tab', 'items');
        $venue = $order->tab?->venue ?? \App\Models\Venue::find($order->venue_id);
        $tenant = $venue?->tenant;
        if (! $tenant || ! $order->whatsapp_consent || ! $order->customer_phone || $tenant->evolutionInstance?->status !== 'connected') return null;
        $phone = $this->normalizePhone($order->customer_phone);
        if (! $phone) return null;
        $template = WhatsappTemplate::where('event', $event)->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))->orderByRaw('tenant_id is null')->first();
        if (! $template?->active) return null;
        $variables = ['{{customer_name}}' => $order->customer_name ?: 'cliente', '{{order_number}}' => (string) $order->id, '{{venue_name}}' => $venue->name, '{{order_total}}' => 'R$ '.number_format((float) $order->total, 2, ',', '.')];
        $body = strtr($template->body, $variables);
        $key = hash('sha256', implode('|', [$tenant->id, $order->id, $event, $phone]));
        $message = OutboundMessage::firstOrCreate(['idempotency_key' => $key], ['tenant_id' => $tenant->id, 'order_id' => $order->id, 'event' => $event, 'recipient' => $phone, 'body' => $body]);
        if ($message->wasRecentlyCreated) SendWhatsappMessage::dispatch($message->id);
        return $message;
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (in_array(strlen($digits), [10, 11], true)) $digits = '55'.$digits;
        return in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55') ? $digits : null;
    }
}
