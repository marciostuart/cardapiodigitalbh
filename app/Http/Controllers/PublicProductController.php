<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function show(Request $request, string $subdomain, Product $product)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $product->venue_id === $venue->id && $product->active, 404);
        $product->load(['images', 'optionGroups' => fn ($query) => $query->where('active', true)->with(['options' => fn ($query) => $query->where('active', true)])]);

        return view('public.product', compact('venue', 'product'));
    }
}
