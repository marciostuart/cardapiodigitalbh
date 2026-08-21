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
        $orders = Order::where('venue_id', $venue->id)
            ->with(['items', 'tab.diningTable'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(40)
            ->withQueryString();
        $allowedTransitions = self::allowedTransitions();

        return view('owner.orders', compact('venue', 'orders', 'allowedTransitions'));
    }

    public function update(Request $request, Order $order)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($order->venue_id === $venue->id, 404);

        $next = $request->validate([
            'status' => ['required', 'in:accepted,preparing,ready,out_for_delivery,completed,cancelled'],
        ])['status'];

        if (!in_array($next, self::allowedTransitions()[$order->status] ?? [], true)) {
            return back()->withErrors(['status' => 'Este pedido já está em outro estágio. Atualize a página e escolha uma das próximas etapas disponíveis.']);
        }

        $updated = DB::transaction(function () use ($order, $next) {
            $current = Order::lockForUpdate()->findOrFail($order->id);

            if (!in_array($next, self::allowedTransitions()[$current->status] ?? [], true)) {
                return false;
            }

            if ($current->status === 'pending' && $next === 'accepted') {
                app(InventoryService::class)->consume($current);
            }
            if ($next === 'cancelled') {
                app(InventoryService::class)->release($current);
            }

            $current->update(['status' => $next]);
            $event = match ($next) {
                'preparing' => 'order_preparing',
                'ready' => 'order_ready',
                'completed' => 'order_completed',
                'cancelled' => 'order_cancelled',
                default => null,
            };
            if ($event) {
                DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($current->fresh(), $event));
            }

            return true;
        });

        if (!$updated) {
            return back()->withErrors(['status' => 'Este pedido foi atualizado por outra pessoa. Atualize a página e tente novamente.']);
        }

        return back()->with('status', 'Pedido atualizado.');
    }

    private static function allowedTransitions(): array
    {
        return [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['out_for_delivery', 'completed', 'cancelled'],
            'out_for_delivery' => ['completed', 'cancelled'],
        ];
    }
}
