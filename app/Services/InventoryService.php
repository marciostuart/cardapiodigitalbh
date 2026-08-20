<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;

class InventoryService
{
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
