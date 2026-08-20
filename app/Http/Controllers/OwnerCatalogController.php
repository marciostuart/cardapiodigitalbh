<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class OwnerCatalogController extends Controller
{
    private function venue(Request $request)
    {
        return $request->user()->tenant?->venues()->firstOrFail();
    }

    public function index(Request $request)
    {
        $venue = $this->venue($request);
        return view('owner.catalog', ['venue' => $venue, 'categories' => ProductCategory::where('venue_id', $venue->id)->with('products')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $venue = $this->venue($request);
        $data = $request->validate(['name' => ['required','string','max:80']]);
        ProductCategory::firstOrCreate(['venue_id' => $venue->id, 'name' => $data['name']]);
        return back()->with('status', 'Categoria cadastrada.');
    }

    public function storeProduct(Request $request)
    {
        $venue = $this->venue($request);
        $data = $request->validate(['product_category_id' => ['required','integer'], 'name' => ['required','string','max:120'], 'price' => ['required','numeric','min:0'], 'station' => ['nullable','in:kitchen,bar']]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        Product::create($data + ['venue_id' => $venue->id, 'active' => true]);
        return back()->with('status', 'Produto cadastrado.');
    }
}
