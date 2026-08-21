<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function show(Request $request, string $subdomain, Product $product)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $product->venue_id === $venue->id && $product->active, 404);
        $product->load(['images', 'optionGroups' => fn ($query) => $query->where('active', true)->with(['options' => fn ($query) => $query->where('active', true)])]);

        $categories = ProductCategory::where('venue_id', $venue->id)->where('active', true)->orderBy('position')->get();
        $purchasedTogetherIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.venue_id', $venue->id)
            ->whereIn('orders.status', ['accepted', 'preparing', 'ready', 'out_for_delivery', 'completed'])
            ->where('order_items.product_id', '!=', $product->id)
            ->whereIn('order_items.order_id', OrderItem::query()->from('order_items as same_order')->select('same_order.order_id')->where('same_order.product_id', $product->id))
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as quantity')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity')
            ->limit(8)
            ->pluck('order_items.product_id');

        $relatedProducts = Product::where('venue_id', $venue->id)->where('active', true)->whereKey($purchasedTogetherIds)->with('images')->get()
            ->sortBy(fn (Product $related) => $purchasedTogetherIds->search($related->id))->values();

        if ($relatedProducts->count() < 4) {
            $fallback = Product::where('venue_id', $venue->id)->where('active', true)->where('id', '!=', $product->id)
                ->where('product_category_id', '!=', $product->product_category_id)->with('images')->orderBy('position')->limit(8)->get();
            $relatedProducts = $relatedProducts->concat($fallback->reject(fn (Product $candidate) => $relatedProducts->contains('id', $candidate->id)))->take(4)->values();
        }

        return view('public.product', compact('venue', 'product', 'categories', 'relatedProducts') + ['isOpen' => true]);
    }
}
