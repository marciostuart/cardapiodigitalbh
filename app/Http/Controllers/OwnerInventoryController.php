<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerInventoryController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        return view('owner.inventory', ['venue' => $venue, 'items' => $venue->inventoryItems()->with('products')->orderBy('name')->get(), 'products' => $venue->products()->orderBy('name')->get(), 'movements' => StockMovement::where('venue_id', $venue->id)->latest()->limit(30)->get()]);
    }

    public function store(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'sku' => ['nullable', 'string', 'max:60'], 'unit' => ['required', 'in:un,kg,g,l,ml'], 'stock_on_hand' => ['required', 'numeric', 'min:0'], 'low_stock_threshold' => ['nullable', 'numeric', 'min:0']]);
        $item = $venue->inventoryItems()->create($data);
        StockMovement::create(['venue_id' => $venue->id, 'inventory_item_id' => $item->id, 'actor_id' => $request->user()->id, 'type' => 'initial', 'quantity' => $item->stock_on_hand, 'balance_after' => $item->stock_on_hand]);
        return back()->with('status', 'Insumo cadastrado.');
    }

    public function adjust(Request $request, InventoryItem $inventoryItem)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($inventoryItem->venue_id === $venue->id, 404);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'not_in:0'], 'notes' => ['required', 'string', 'max:200']]);
        DB::transaction(function () use ($request, $inventoryItem, $data, $venue) {
            $item = InventoryItem::lockForUpdate()->findOrFail($inventoryItem->id);
            $balance = (float) $item->stock_on_hand + (float) $data['quantity'];
            abort_if($balance < 0 || $balance < (float) $item->stock_reserved, 422, 'O ajuste deixaria o estoque abaixo da quantidade reservada.');
            $item->update(['stock_on_hand' => $balance]);
            StockMovement::create(['venue_id' => $venue->id, 'inventory_item_id' => $item->id, 'actor_id' => $request->user()->id, 'type' => (float) $data['quantity'] > 0 ? 'entry' : 'loss', 'quantity' => $data['quantity'], 'balance_after' => $balance, 'notes' => $data['notes']]);
        });
        return back()->with('status', 'Estoque ajustado.');
    }

    public function recipe(Request $request, Product $product)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($product->venue_id === $venue->id, 404);
        $data = $request->validate(['inventory_item_id' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0']]);
        abort_unless(InventoryItem::whereKey($data['inventory_item_id'])->where('venue_id', $venue->id)->exists(), 422);
        $product->ingredients()->syncWithoutDetaching([$data['inventory_item_id'] => ['quantity' => $data['quantity']]]);
        return back()->with('status', 'Insumo adicionado à ficha técnica.');
    }
}
