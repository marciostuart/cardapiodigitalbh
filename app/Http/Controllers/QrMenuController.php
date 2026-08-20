<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\ProductCategory;

class QrMenuController extends Controller
{
    public function show(string $token)
    {
        $table = DiningTable::where('qr_token', $token)
            ->whereHas('venue.tenant', fn ($query) => $query->where('status', 'active'))
            ->with('venue')
            ->firstOrFail();
        $venue = $table->venue;

        return view('public.menu', ['venue' => $venue, 'table' => $table, 'categories' => ProductCategory::where('venue_id', $venue->id)->where('active', true)->with(['products' => fn ($query) => $query->where('active', true)->orderBy('position')])->orderBy('position')->get()]);
    }
}
