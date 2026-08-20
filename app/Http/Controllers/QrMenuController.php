<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\ProductCategory;
use App\Support\CatalogOrdering;
use Illuminate\Http\Request;
use App\Support\VenueAvailability;

class QrMenuController extends Controller
{
    public function show(Request $request, string $token)
    {
        $table = DiningTable::where('qr_token', $token)
            ->whereHas('venue.tenant', fn ($query) => $query->where('status', 'active'))
            ->with('venue')
            ->firstOrFail();
        $venue = $table->venue;
        $resolvedVenue = $request->attributes->get('venue');
        abort_if($resolvedVenue && $resolvedVenue->id !== $venue->id, 404);

        return view('public.menu', ['venue' => $venue, 'table' => $table, 'isOpen' => VenueAvailability::isOpen($venue), 'deliveryZones' => collect(), 'categories' => ProductCategory::where('venue_id', $venue->id)->where('active', true)->with(['products' => fn ($query) => CatalogOrdering::apply($query->where('active', true), $venue->catalog_sort_mode)->with(['optionGroups' => fn ($q) => $q->where('active', true)->with(['options' => fn ($q) => $q->where('active', true)])])])->orderBy('position')->get()]);
    }
}
