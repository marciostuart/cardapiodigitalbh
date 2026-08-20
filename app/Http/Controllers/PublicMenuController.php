<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function show(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        return response()->json(['venue' => $venue->only('name', 'subdomain')]);
    }
}
