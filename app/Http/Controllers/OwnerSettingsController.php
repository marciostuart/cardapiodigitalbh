<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OwnerSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant?->venues()->firstOrFail();

        return view('owner.settings', compact('tenant', 'venue'));
    }

    public function update(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant?->venues()->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'in:America/Sao_Paulo,America/Manaus,America/Cuiaba,America/Belem,America/Fortaleza,America/Recife'],
            'catalog_sort_mode' => ['sometimes', 'in:manual,popularity'],
            'geofence_enabled' => ['sometimes', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_if:geofence_enabled,1'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_if:geofence_enabled,1'],
            'geofence_radius_meters' => ['nullable', 'integer', 'between:20,1000', 'required_if:geofence_enabled,1'],
            'table_service_enabled' => ['sometimes', 'boolean'],
            'pickup_enabled' => ['sometimes', 'boolean'],
            'delivery_enabled' => ['sometimes', 'boolean'],
            'minimum_order' => ['sometimes', 'numeric', 'min:0'],
        ]);
        $venue->update($data);
        $tenant->update(['name' => $data['name']]);

        return back()->with('status', 'Dados do estabelecimento atualizados.');
    }
}
