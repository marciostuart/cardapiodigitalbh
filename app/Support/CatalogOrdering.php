<?php

namespace App\Support;

class CatalogOrdering
{
    public static function apply($query, string $mode)
    {
        if ($mode === 'manual') {
            return $query->orderBy('position')->orderBy('id');
        }

        return $query
            ->withSum([
                'orderItems as popularity_score' => fn ($items) => $items->whereHas(
                    'order',
                    fn ($orders) => $orders->whereIn('status', ['accepted', 'preparing', 'ready', 'out_for_delivery', 'completed'])
                ),
            ], 'quantity')
            ->orderByDesc('popularity_score')
            ->orderBy('position')
            ->orderBy('id');
    }
}
