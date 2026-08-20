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
        ]);
        $venue->update($data);
        $tenant->update(['name' => $data['name']]);

        return back()->with('status', 'Dados do estabelecimento atualizados.');
    }
}
