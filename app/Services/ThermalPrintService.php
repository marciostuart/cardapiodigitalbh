<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrintJob;
use App\Models\PrinterAgentDevice;

class ThermalPrintService
{
    public function queueKitchenTicket(Order $order): ?PrintJob
    {
        return $this->queue($order, 'kitchen_ticket', 'order:'.$order->id.':kitchen:v1');
    }

    public function queueKitchenTicketAgain(Order $order): ?PrintJob
    {
        return $this->queue($order, 'kitchen_ticket_reprint', 'order:'.$order->id.':kitchen-reprint:'.\Illuminate\Support\Str::uuid());
    }

    private function queue(Order $order, string $kind, string $jobKey): ?PrintJob
    {
        $device = PrinterAgentDevice::where('tenant_id', $order->venue->tenant_id)
            ->where('venue_id', $order->venue_id)
            ->where('active', true)
            ->where('is_primary', true)
            ->first();
        if (! $device) {
            return null;
        }

        $order->loadMissing(['venue', 'items']);
        $payload = [
            'order_id' => $order->id,
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
            'venue_name' => $order->venue->name,
            'customer_name' => $order->customer_name ?: 'Cliente',
            'channel' => trans('status.channel.'.$order->channel),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->name_snapshot,
                'quantity' => (int) $item->quantity,
                'options' => collect($item->options_snapshot ?? [])->pluck('name')->filter()->values()->all(),
                'notes' => $item->notes,
            ])->all(),
            'notes' => $order->kitchen_notes ?: $order->notes,
            'total' => number_format((float) $order->total, 2, ',', '.'),
        ];

        return PrintJob::firstOrCreate(
            ['job_key' => $jobKey],
            [
                'tenant_id' => $order->venue->tenant_id,
                'venue_id' => $order->venue_id,
                'order_id' => $order->id,
                'printer_agent_device_id' => $device->id,
                'kind' => $kind,
                'payload' => $payload,
                'status' => 'queued',
            ],
        );
    }
}
