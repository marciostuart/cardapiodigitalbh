<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueRequest;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class VenueRegistrationController extends Controller
{
    public function store(StoreVenueRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $tenant = Tenant::create(['name' => $request->tenant_name, 'slug' => $request->subdomain]);
            $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $request->venue_name, 'subdomain' => $request->subdomain]);
            return response()->json(compact('tenant', 'venue'), 201);
        });
    }
}
