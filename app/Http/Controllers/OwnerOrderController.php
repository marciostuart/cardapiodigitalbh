<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use App\Support\OrderLineBuilder;
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

    public function show(Request $request, Order $order)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($order->venue_id === $venue->id, 404);
        $order->load(['items.product.optionGroups.options', 'tab.diningTable']);
        $products = Product::where('venue_id', $venue->id)->where('active', true)->with(['optionGroups' => fn ($query) => $query->where('active', true)->with(['options' => fn ($options) => $options->where('active', true)])])->orderBy('name')->get();
        $editable = !in_array($order->status, ['completed', 'cancelled', 'refunded'], true);
        $catalog = $products->map(fn ($product) => ['id' => $product->id, 'name' => $product->name, 'groups' => $product->optionGroups->map(fn ($group) => ['name' => $group->name, 'minimum' => $group->minimum, 'maximum' => $group->maximum, 'options' => $group->options->map(fn ($option) => ['id' => $option->id, 'name' => $option->name, 'price' => (float) $option->price])->values()])->values()])->values();
        return view('owner.order-details', compact('order', 'products', 'editable', 'catalog'));
    }

    public function updateDetails(Request $request, Order $order)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($order->venue_id === $venue->id, 404);
        abort_if(in_array($order->status, ['completed', 'cancelled', 'refunded'], true), 422, 'Pedidos concluídos ou cancelados podem apenas ser visualizados.');
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000'], 'kitchen_notes' => ['nullable', 'string', 'max:1000'], 'delivery_notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'integer'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'], 'items.*.option_ids' => ['nullable', 'array'], 'items.*.option_ids.*' => ['integer']]);

        DB::transaction(function () use ($order, $venue, $data) {
            $current = Order::lockForUpdate()->findOrFail($order->id);
            abort_if(in_array($current->status, ['completed', 'cancelled', 'refunded'], true), 422, 'Este pedido não pode mais ser alterado.');
            $previous = $current->items()->get();
            $lines = OrderLineBuilder::build($venue->id, $data['items']);
            app(InventoryService::class)->adjustEditedOrder($current, $previous, $lines);
            $subtotal = collect($lines)->sum('total');
            $discount = min((float) $current->discount, $subtotal);
            $current->update(['notes' => $data['notes'] ?: null, 'kitchen_notes' => $data['kitchen_notes'] ?: null, 'delivery_notes' => $data['delivery_notes'] ?: null, 'subtotal' => $subtotal, 'discount' => $discount, 'total' => max(0, $subtotal - $discount + (float) $current->delivery_fee)]);
            $current->items()->delete();
            foreach ($lines as $line) $current->items()->create(['product_id' => $line['product']->id, 'name_snapshot' => $line['product']->name, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'total' => $line['total'], 'station' => $line['product']->station, 'status' => $current->status === 'ready' ? 'ready' : ($current->status === 'preparing' ? 'preparing' : 'pending'), 'options_snapshot' => $line['options']]);
        });
        return redirect()->route('owner.orders.show', $order)->with('status', 'Detalhes e itens do pedido atualizados.');
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
