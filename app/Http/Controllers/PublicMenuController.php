<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function show(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        return view('public.menu', [
            'venue' => $venue,
            'categories' => \App\Models\ProductCategory::where('venue_id', $venue->id)
                ->where('active', true)
                ->with(['products' => fn ($query) => $query->where('active', true)->orderBy('position')])
                ->orderBy('position')->get(),
        ]);
    }
}
