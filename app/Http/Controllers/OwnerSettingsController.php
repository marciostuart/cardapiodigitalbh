<?php

namespace App\Http\Controllers;

use App\Services\TenantMedia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class OwnerSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant?->venues()->firstOrFail();

        $banners = $venue->banners()->orderBy('position')->get();

        return view('owner.settings', compact('tenant', 'venue', 'banners'));
    }

    public function update(Request $request, TenantMedia $media)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant?->venues()->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subdomain' => ['sometimes', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('venues', 'subdomain')->ignore($venue->id), Rule::notIn(['www', 'admin', 'api', 'mail', 'app', 'painel'])],
            'confirm_slug_change' => ['sometimes', 'accepted'],
            'primary_color' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', File::image()->max(5 * 1024)],
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
        $data['subdomain'] ??= $venue->subdomain;
        if ($data['subdomain'] !== $venue->subdomain) {
            abort_unless($request->boolean('confirm_slug_change'), 422, 'Confirme que compreende o impacto da alteração do endereço.');
        }
        unset($data['confirm_slug_change'],$data['logo']);
        if ($request->hasFile('logo')) {
            $old = $venue->logo_path;
            $data['logo_path'] = $media->store($tenant, $request->file('logo'), 'logos', $data['name'].' logo cardapio digital');
            $media->delete($old, $tenant);
        }
        $venue->update($data);
        $tenant->update(['name' => $data['name']]);

        return back()->with('status', 'Dados do estabelecimento atualizados.');
    }
}
