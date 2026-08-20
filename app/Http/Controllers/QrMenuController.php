<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\ProductCategory;
use App\Support\CatalogOrdering;
use App\Support\VenueAvailability;
use Illuminate\Http\Request;

class QrMenuController extends Controller
{
    public function show(Request $request, string $token)
    {
        $table = DiningTable::where('qr_token', $token)->whereHas('venue.tenant', fn ($q) => $q->where('status', 'active'))->with('venue')->firstOrFail();
        $venue = $table->venue;
        $resolved = $request->attributes->get('venue');
        abort_if($resolved && $resolved->id !== $venue->id, 404);
        $banners = $venue->banners()->where('active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderBy('position')->get();
        $categories = ProductCategory::where('venue_id', $venue->id)->where('active', true)->with(['products' => fn ($q) => CatalogOrdering::apply($q->where('active', true), $venue->catalog_sort_mode)->with(['images', 'optionGroups' => fn ($q) => $q->where('active', true)->with(['options' => fn ($q) => $q->where('active', true)])])])->orderBy('position')->get();

        return view('public.menu', compact('venue', 'table', 'banners', 'categories') + ['isOpen' => VenueAvailability::isOpen($venue), 'deliveryZones' => collect()]);
    }
}
