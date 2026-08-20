<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Services\TenantMedia;
use App\Support\CatalogOrdering;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

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

        return view('owner.catalog', [
            'venue' => $venue,
            'categories' => ProductCategory::where('venue_id', $venue->id)
                ->with(['products' => fn ($query) => CatalogOrdering::apply($query, $venue->catalog_sort_mode)->with('optionGroups.options')])
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        ]);
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

    public function storeProduct(Request $request, TenantMedia $media)
    {
        $venue = $this->venue($request);
        $data = $request->validate(['product_category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:120'], 'sku' => ['nullable', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:1000'], 'price' => ['required', 'numeric', 'min:0'], 'image' => ['nullable', File::image()->max(5 * 1024)], 'station' => ['nullable', 'in:kitchen,bar'], 'position' => ['nullable', 'integer', 'min:0'], 'track_stock' => ['nullable', 'boolean'], 'stock_on_hand' => ['nullable', 'numeric', 'min:0'], 'low_stock_threshold' => ['nullable', 'numeric', 'min:0']]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        $data['position'] ??= ((int) Product::where('product_category_id', $data['product_category_id'])->max('position')) + 10;
        unset($data['image']);
        if ($request->hasFile('image')) {
            $data['image_path'] = $media->store($request->user()->tenant, $request->file('image'), 'products', $venue->name.' '.$data['name']);
        } Product::create($data + ['venue_id' => $venue->id, 'active' => true]);

        return back()->with('status', 'Produto cadastrado.');
    }

    public function updateProduct(Request $request, Product $product, TenantMedia $media)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $data = $request->validate([
            'product_category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', File::image()->max(5 * 1024)],
            'station' => ['required', 'in:kitchen,bar'],
            'active' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'stock_on_hand' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        unset($data['image']);
        if ($request->hasFile('image')) {
            $old = $product->image_path;
            $data['image_path'] = $media->store($request->user()->tenant, $request->file('image'), 'products', $venue->name.' '.$data['name']);
            $media->delete($old, $request->user()->tenant);
        } $product->update($data);

        return back()->with('status', 'Produto atualizado.');
    }

    public function storeOptionGroup(Request $request, Product $product)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'minimum' => ['required', 'integer', 'min:0', 'max:30'], 'maximum' => ['required', 'integer', 'min:1', 'max:30', 'gte:minimum']]);
        $product->optionGroups()->create($data + ['position' => ((int) $product->optionGroups()->max('position')) + 10]);

        return back()->with('status', 'Grupo de adicionais criado.');
    }

    public function updateOptionGroup(Request $request, ProductOptionGroup $group)
    {
        $venue = $this->venue($request);
        abort_unless($group->product()->where('venue_id', $venue->id)->exists(), 404);
        $group->update($request->validate(['name' => ['required', 'string', 'max:80'], 'minimum' => ['required', 'integer', 'min:0', 'max:30'], 'maximum' => ['required', 'integer', 'min:1', 'max:30', 'gte:minimum'], 'active' => ['required', 'boolean']]));

        return back()->with('status', 'Grupo atualizado.');
    }

    public function destroyOptionGroup(Request $request, ProductOptionGroup $group)
    {
        $venue = $this->venue($request);
        abort_unless($group->product()->where('venue_id', $venue->id)->exists(), 404);
        $group->delete();

        return back()->with('status', 'Grupo removido.');
    }

    public function storeOption(Request $request, ProductOptionGroup $group)
    {
        $venue = $this->venue($request);
        abort_unless($group->product()->where('venue_id', $venue->id)->exists(), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:300'], 'price' => ['required', 'numeric', 'min:0']]);
        $group->options()->create($data + ['position' => ((int) $group->options()->max('position')) + 10]);

        return back()->with('status', 'Opção adicionada.');
    }

    public function updateOption(Request $request, ProductOption $option)
    {
        $venue = $this->venue($request);
        abort_unless($option->group()->whereHas('product', fn ($q) => $q->where('venue_id', $venue->id))->exists(), 404);
        $option->update($request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:300'], 'price' => ['required', 'numeric', 'min:0'], 'active' => ['required', 'boolean']]));

        return back()->with('status', 'Opção atualizada.');
    }

    public function destroyOption(Request $request, ProductOption $option)
    {
        $venue = $this->venue($request);
        abort_unless($option->group()->whereHas('product', fn ($q) => $q->where('venue_id', $venue->id))->exists(), 404);
        $option->delete();

        return back()->with('status', 'Opção removida.');
    }

    public function destroyProduct(Request $request, Product $product)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $product->delete();

        return back()->with('status', 'Produto excluído.');
    }
}
