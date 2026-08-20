<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Services\TenantMedia;
use App\Support\CatalogOrdering;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

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

    public function createProduct(Request $request)
    {
        $venue = $this->venue($request);
        $categories = ProductCategory::where('venue_id', $venue->id)->orderBy('position')->orderBy('name')->get();

        return view('owner.product-form', compact('venue', 'categories'));
    }

    public function editProduct(Request $request, Product $product)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $product->load(['optionGroups.options', 'images']);
        $categories = ProductCategory::where('venue_id', $venue->id)->orderBy('position')->orderBy('name')->get();

        return view('owner.product-form', compact('venue', 'categories', 'product'));
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
        $data = $request->validate(['product_category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:120'], 'sku' => ['nullable', 'string', 'max:60'], 'description' => ['nullable', 'string', 'max:1000'], 'price' => ['required', 'numeric', 'min:0'], 'image' => ['nullable', File::image()->max(12 * 1024)], 'gallery_images' => ['nullable', 'array', 'max:4'], 'gallery_images.*' => ['required', File::image()->max(12 * 1024)], 'station' => ['nullable', 'in:kitchen,bar'], 'position' => ['nullable', 'integer', 'min:0'], 'track_stock' => ['nullable', 'boolean'], 'stock_on_hand' => ['nullable', 'numeric', 'min:0'], 'low_stock_threshold' => ['nullable', 'numeric', 'min:0']]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        $data['position'] ??= ((int) Product::where('product_category_id', $data['product_category_id'])->max('position')) + 10;
        unset($data['image'], $data['gallery_images']);
        if ($request->hasFile('image')) {
            $data['image_path'] = $media->store($request->user()->tenant, $request->file('image'), 'products', $venue->name.' '.$data['name']);
        }
        $product = Product::create($data + ['venue_id' => $venue->id, 'active' => true]);
        $this->storeGalleryImages($request, $product, $media, $venue->name);

        return redirect()->route('owner.products.edit', $product)->with('status', 'Produto cadastrado. Agora você pode adicionar variações.');
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
            'image' => ['nullable', File::image()->max(12 * 1024)],
            'gallery_images' => ['nullable', 'array', 'max:4'],
            'gallery_images.*' => ['required', File::image()->max(12 * 1024)],
            'station' => ['required', 'in:kitchen,bar'],
            'active' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'stock_on_hand' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);
        abort_unless(ProductCategory::whereKey($data['product_category_id'])->where('venue_id', $venue->id)->exists(), 422);
        $newGalleryCount = count($request->file('gallery_images', []));
        if ($product->images()->count() + $newGalleryCount > 4) {
            throw ValidationException::withMessages(['gallery_images' => 'A galeria permite no máximo quatro imagens, além da foto de destaque.']);
        }
        unset($data['image'], $data['gallery_images']);
        if ($request->hasFile('image')) {
            $old = $product->image_path;
            $data['image_path'] = $media->store($request->user()->tenant, $request->file('image'), 'products', $venue->name.' '.$data['name']);
            $media->delete($old, $request->user()->tenant);
        }
        $product->update($data);
        $this->storeGalleryImages($request, $product, $media, $venue->name);

        return redirect()->route('owner.products.edit', $product)->with('status', 'Produto atualizado.');
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

    public function destroyProduct(Request $request, Product $product, TenantMedia $media)
    {
        $venue = $this->venue($request);
        abort_unless($product->venue_id === $venue->id, 404);
        $media->delete($product->image_path, $request->user()->tenant);
        foreach ($product->images as $image) {
            $media->delete($image->path, $request->user()->tenant);
        }
        $product->delete();

        return redirect()->route('owner.catalog')->with('status', 'Produto excluído.');
    }

    public function destroyProductImage(Request $request, ProductImage $image, TenantMedia $media)
    {
        $venue = $this->venue($request);
        abort_unless($image->product()->where('venue_id', $venue->id)->exists(), 404);
        $media->delete($image->path, $request->user()->tenant);
        $image->delete();

        return back()->with('status', 'Imagem removida da galeria.');
    }

    private function storeGalleryImages(Request $request, Product $product, TenantMedia $media, string $venueName): void
    {
        $position = (int) $product->images()->max('position');
        foreach ($request->file('gallery_images', []) as $index => $image) {
            $path = $media->store($request->user()->tenant, $image, 'products', $venueName.' '.$product->name.' galeria '.($index + 1));
            $product->images()->create(['path' => $path, 'position' => ++$position]);
        }
    }
}
