<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerOrderController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $orders = Order::where('venue_id', $venue->id)->with(['items', 'tab.diningTable'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->paginate(40)->withQueryString();
        return view('owner.orders', compact('venue', 'orders'));
    }

    public function update(Request $request, Order $order)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($order->venue_id === $venue->id, 404);
        $next = $request->validate(['status' => ['required', 'in:accepted,preparing,ready,out_for_delivery,completed,cancelled']])['status'];
        $allowed = ['pending' => ['accepted', 'cancelled'], 'accepted' => ['preparing', 'cancelled'], 'preparing' => ['ready', 'cancelled'], 'ready' => ['out_for_delivery', 'completed', 'cancelled'], 'out_for_delivery' => ['completed', 'cancelled']];
        abort_unless(in_array($next, $allowed[$order->status] ?? [], true), 422, 'Transição de status não permitida.');
        DB::transaction(function () use ($order, $next) {
            $current = Order::lockForUpdate()->findOrFail($order->id);
            if ($current->status === 'pending' && $next === 'accepted') app(InventoryService::class)->consume($current);
            if ($next === 'cancelled') app(InventoryService::class)->release($current);
            $current->update(['status' => $next]);
            $event = match ($next) { 'preparing' => 'order_preparing', 'ready' => 'order_ready', 'completed' => 'order_completed', 'cancelled' => 'order_cancelled', default => null };
            if ($event) DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($current->fresh(), $event));
        });
        return back()->with('status', 'Pedido atualizado.');
    }
}
