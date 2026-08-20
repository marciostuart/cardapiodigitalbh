<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class OwnerCatalogController extends Controller
{
    private function venue(Request $request)
    {
        $venue = $request->user()->tenant?->venues()->first();
        abort_unless($venue, 404, 'Estabelecimento não encontrado para esta conta.');

        return $venue;
    }

    public function index(Request $request)
    {
        $venue = $this->venue($request);

        return view('owner.catalog', ['venue' => $venue, 'categories' => ProductCategory::where('venue_id', $venue->id)->with('products')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $venue = $this->venue($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        ProductCategory::firstOrCreate(['venue_id' => $venue->id, 'name' => $data['name']]);

        return back()->with('status', 'Categoria cadastrada.');
    }

    public function updateCategory(Request $request, ProductCategory $category)
    {
        $venue = $this->venue($request);
        abort_unless($category->venue_id === $venue->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'active' => ['required', 'boolean']]);
        $category->update($data);

        return back()->with('status', 'Categoria atualizada.');
    }

    public function destroyCategory(Request $request, ProductCategory $category)
    {
        $venue = $this->venue($request);
        abort_unless($category->venue_id === $venue->id, 404);
        $category->delete();

        return back()->with('status', 'Categoria excluída.');
    }

    public function storeProduct(Request $request)
    {
        $venue = $this->venue($request);
        $data = $request->validate(['product_category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'price' => ['required', 'numeric', 'min:0'], 'station' => ['nullable', 'in:kitchen,bar']]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        Product::create($data + ['venue_id' => $venue->id, 'active' => true]);

        return back()->with('status', 'Produto cadastrado.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $data = $request->validate([
            'product_category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'station' => ['required', 'in:kitchen,bar'],
            'active' => ['required', 'boolean'],
        ]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        $product->update($data);

        return back()->with('status', 'Produto atualizado.');
    }

    public function destroyProduct(Request $request, Product $product)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $product->delete();

        return back()->with('status', 'Produto excluído.');
    }
}
