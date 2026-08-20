<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductOption;

class OrderLineBuilder
{
    public static function build(int $venueId, array $lines): array
    {
        $ids = collect($lines)->pluck('product_id')->unique();
        $products = Product::where('venue_id', $venueId)->where('active', true)
            ->whereIn('id', $ids)->with(['optionGroups' => fn ($q) => $q->where('active', true)->with(['options' => fn ($q) => $q->where('active', true)])])->get()->keyBy('id');
        abort_unless($products->count() === $ids->count(), 422, 'Produto indisponível para este estabelecimento.');

        return collect($lines)->map(function (array $line) use ($products) {
            $product = $products[$line['product_id']];
            $selectedIds = collect($line['option_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $validOptions = ProductOption::whereIn('id', $selectedIds)->where('active', true)
                ->whereHas('group', fn ($q) => $q->where('product_id', $product->id)->where('active', true))->get()->keyBy('id');
            abort_unless($validOptions->count() === $selectedIds->count(), 422, 'Um adicional selecionado não pertence ao produto.');

            foreach ($product->optionGroups as $group) {
                $count = $validOptions->where('product_option_group_id', $group->id)->count();
                abort_if($count < $group->minimum || $count > $group->maximum, 422, "Selecione entre {$group->minimum} e {$group->maximum} opção(ões) em {$group->name}.");
            }

            $options = $validOptions->map(fn ($option) => ['id' => $option->id, 'name' => $option->name, 'description' => $option->description, 'price' => (float) $option->price])->values()->all();
            $unitPrice = (float) $product->price + collect($options)->sum('price');
            $quantity = (int) $line['quantity'];

            return ['product' => $product, 'quantity' => $quantity, 'options' => $options, 'unit_price' => $unitPrice, 'total' => $unitPrice * $quantity];
        })->all();
    }
}
