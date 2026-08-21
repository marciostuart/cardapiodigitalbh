<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;

class InventoryService
{
    public function adjustEditedOrder(Order $order, $previousItems, array $lines): void
    {
        $old = collect($previousItems)->groupBy('product_id')->map(fn ($items) => (float) $items->sum('quantity'));
        $new = collect($lines)->groupBy(fn ($line) => $line['product']->id)->map(fn ($items) => (float) collect($items)->sum('quantity'));
        $consumed = $order->status !== 'pending';

        foreach ($old->keys()->merge($new->keys())->unique() as $productId) {
            $delta = (float) ($new[$productId] ?? 0) - (float) ($old[$productId] ?? 0);
            if (abs($delta) < 0.0001) continue;
            $product = Product::with('ingredients')->lockForUpdate()->findOrFail($productId);
            if ($product->track_stock) $this->adjustEditedBalance($order, $product, $delta, $consumed);
            foreach ($product->ingredients as $ingredient) $this->adjustEditedBalance($order, InventoryItem::lockForUpdate()->findOrFail($ingredient->id), $delta * (float) $ingredient->pivot->quantity, $consumed, $product);
        }
    }

    private function adjustEditedBalance(Order $order, $item, float $delta, bool $consumed, ?Product $product = null): void
    {
        $available = (float) $item->stock_on_hand - (float) $item->stock_reserved;
        if ($delta > 0) $this->ensureAvailable($available, 0, $delta, $item->name);
        if ($consumed) $item->update(['stock_on_hand' => max(0, (float) $item->stock_on_hand - $delta)]);
        else $item->update(['stock_reserved' => max(0, (float) $item->stock_reserved + $delta)]);
        StockMovement::create(['venue_id' => $order->venue_id, 'inventory_item_id' => $item instanceof InventoryItem ? $item->id : null, 'product_id' => $product?->id ?? ($item instanceof Product ? $item->id : null), 'order_id' => $order->id, 'type' => $consumed ? 'order_edit_consume' : 'order_edit_reserve', 'quantity' => $consumed ? -$delta : $delta, 'balance_after' => $consumed ? (float) $item->fresh()->stock_on_hand : (float) $item->fresh()->stock_on_hand - (float) $item->fresh()->stock_reserved, 'notes' => 'Ajuste do pedido pelo atendimento']);
    }
    public function reserve(Order $order): void
    {
        $order->loadMissing('items.product.ingredients');
        foreach ($order->items as $line) {
            $product = Product::lockForUpdate()->find($line->product_id);
            if (! $product) continue;
            $quantity = (float) $line->quantity;
            if ($product->track_stock) {
                $this->ensureAvailable((float) $product->stock_on_hand, (float) $product->stock_reserved, $quantity, $product->name);
                $product->increment('stock_reserved', $quantity);
                $this->movement($order, 'reserve', $quantity, (float) $product->fresh()->stock_on_hand - (float) $product->fresh()->stock_reserved, product: $product);
            }
            foreach ($product->ingredients as $recipeLine) {
                $ingredient = InventoryItem::lockForUpdate()->findOrFail($recipeLine->id);
                $required = (float) $recipeLine->pivot->quantity * $quantity;
                $this->ensureAvailable((float) $ingredient->stock_on_hand, (float) $ingredient->stock_reserved, $required, $ingredient->name);
                $ingredient->increment('stock_reserved', $required);
                $this->movement($order, 'reserve', $required, (float) $ingredient->fresh()->stock_on_hand - (float) $ingredient->fresh()->stock_reserved, ingredient: $ingredient, product: $product);
            }
        }
    }

    public function consume(Order $order): void
    {
        $order->loadMissing('items.product.ingredients');
        foreach ($order->items as $line) {
            $product = Product::lockForUpdate()->find($line->product_id);
            if (! $product) continue;
            $quantity = (float) $line->quantity;
            if ($product->track_stock && ! $this->moved($order, 'consume', product: $product)) {
                $product->update(['stock_reserved' => max(0, (float) $product->stock_reserved - $quantity), 'stock_on_hand' => max(0, (float) $product->stock_on_hand - $quantity)]);
                $this->movement($order, 'consume', -$quantity, (float) $product->fresh()->stock_on_hand, product: $product);
            }
            foreach ($product->ingredients as $recipeLine) {
                $ingredient = InventoryItem::lockForUpdate()->findOrFail($recipeLine->id);
                if ($this->moved($order, 'consume', $ingredient, $product)) continue;
                $required = (float) $recipeLine->pivot->quantity * $quantity;
                $ingredient->update(['stock_reserved' => max(0, (float) $ingredient->stock_reserved - $required), 'stock_on_hand' => max(0, (float) $ingredient->stock_on_hand - $required)]);
                $this->movement($order, 'consume', -$required, (float) $ingredient->fresh()->stock_on_hand, $ingredient, $product);
            }
        }
    }

    public function release(Order $order): void
    {
        $order->loadMissing('items.product.ingredients');
        foreach ($order->items as $line) {
            $product = Product::lockForUpdate()->find($line->product_id);
            if (! $product) continue;
            $quantity = (float) $line->quantity;
            if ($product->track_stock && ! $this->moved($order, 'consume', product: $product) && ! $this->moved($order, 'release', product: $product)) {
                $product->update(['stock_reserved' => max(0, (float) $product->stock_reserved - $quantity)]);
                $this->movement($order, 'release', -$quantity, (float) $product->fresh()->stock_on_hand - (float) $product->fresh()->stock_reserved, product: $product);
            }
            foreach ($product->ingredients as $recipeLine) {
                $ingredient = InventoryItem::lockForUpdate()->findOrFail($recipeLine->id);
                if ($this->moved($order, 'consume', $ingredient, $product) || $this->moved($order, 'release', $ingredient, $product)) continue;
                $required = (float) $recipeLine->pivot->quantity * $quantity;
                $ingredient->update(['stock_reserved' => max(0, (float) $ingredient->stock_reserved - $required)]);
                $this->movement($order, 'release', -$required, (float) $ingredient->fresh()->stock_on_hand - (float) $ingredient->fresh()->stock_reserved, $ingredient, $product);
            }
        }
    }

    private function ensureAvailable(float $onHand, float $reserved, float $required, string $name): void
    {
        abort_if(($onHand - $reserved + 0.0001) < $required, 422, 'Estoque insuficiente para '.$name.'.');
    }

    private function moved(Order $order, string $type, ?InventoryItem $ingredient = null, ?Product $product = null): bool
    {
        return StockMovement::where('order_id', $order->id)->where('type', $type)
            ->when($ingredient, fn ($q) => $q->where('inventory_item_id', $ingredient->id), fn ($q) => $q->whereNull('inventory_item_id'))
            ->when($product, fn ($q) => $q->where('product_id', $product->id))
            ->exists();
    }

    private function movement(Order $order, string $type, float $quantity, float $balance, ?InventoryItem $ingredient = null, ?Product $product = null): void
    {
        StockMovement::create(['venue_id' => $order->venue_id, 'inventory_item_id' => $ingredient?->id, 'product_id' => $product?->id, 'order_id' => $order->id, 'type' => $type, 'quantity' => $quantity, 'balance_after' => $balance]);
    }
}
