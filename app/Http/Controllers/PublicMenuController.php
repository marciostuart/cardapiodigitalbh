<?php

namespace App\Http\Controllers;

use App\Support\CatalogOrdering;
use App\Support\VenueAvailability;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function show(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        return view('public.menu', [
            'venue' => $venue,
            'isOpen' => VenueAvailability::isOpen($venue),
            'deliveryZones' => $venue->deliveryZones()->where('active', true)->orderBy('name')->get(),
            'categories' => \App\Models\ProductCategory::where('venue_id', $venue->id)
                ->where('active', true)
                ->with(['products' => fn ($query) => CatalogOrdering::apply($query->where('active', true), $venue->catalog_sort_mode)])
                ->orderBy('position')->get(),
        ]);
    }
}
