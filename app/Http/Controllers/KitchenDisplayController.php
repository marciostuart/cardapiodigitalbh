<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderItemStatusRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\InventoryService;
use App\Services\ThermalPrintService;
use App\Services\WhatsappNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenDisplayController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        $orders = Order::where('venue_id', $venue->id)
            ->whereIn('status', ['pending', 'accepted', 'preparing'])
            ->where(fn ($query) => $query->where('payment_mode', '!=', 'online')->orWhere('payment_status', 'approved'))
            ->with(['items', 'tab.diningTable'])
            ->oldest()
            ->get();

        return $request->expectsJson()
            ? response()->json($orders)
            : view('operations.kds', compact('venue', 'orders'));
    }

    public function update(UpdateOrderItemStatusRequest $request, OrderItem $item)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $item->order->venue_id === $venue->id, 404);
        abort_unless($item->order->payment_mode !== 'online' || $item->order->payment_status === 'approved', 409, 'A cozinha só pode iniciar um pedido após a confirmação do pagamento.');
        DB::transaction(function () use ($request, $item) {
            $order = Order::lockForUpdate()->findOrFail($item->order_id);
            $wasPending = $order->status === 'pending';
            $item->update(['status' => $request->validated('status')]);
            $newStatus = $this->synchronizeOrderStatus($order->fresh('items'));
            if ($wasPending) app(InventoryService::class)->consume($order);
            if ($wasPending) DB::afterCommit(fn () => app(ThermalPrintService::class)->queueKitchenTicket($order->fresh()));
            $event = match ($newStatus) { 'preparing' => 'order_preparing', 'ready' => 'order_ready', default => null };
            if ($event) DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), $event));
        });

        return $request->expectsJson()
            ? response()->json($item->fresh())
            : back()->with('status', 'Status do item atualizado.');
    }

    private function synchronizeOrderStatus(Order $order): string
    {
        $statuses = $order->items->pluck('status');
        $status = $statuses->every(fn ($value) => in_array($value, ['ready', 'cancelled'], true))
            ? 'ready'
            : ($statuses->contains('preparing') ? 'preparing' : 'accepted');
        $order->update(['status' => $status]);
        return $status;
    }
}
