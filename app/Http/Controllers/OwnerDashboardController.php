<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Tab;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant?->venues()->first();

        if ($venue && $venue->diningTables()->count() === 0) {
            return redirect()->route('owner.tables')->with('status', 'Comece cadastrando a primeira mesa.');
        }

        return view('dashboard', [
            'venue' => $venue,
            'openTabs' => $venue ? Tab::where('venue_id', $venue->id)->where('status', 'open')->count() : 0,
            'todaySales' => $venue ? Order::where('venue_id', $venue->id)->where('status', '!=', 'cancelled')->where('created_at', '>=', now()->startOfDay())->sum('total') : 0,
            'tables' => $venue ? $venue->diningTables()->count() : 0,
            'publicMenuUrl' => $venue ? 'https://'.$venue->subdomain.'.cardapiodigitalbh.com.br' : null,
        ]);
    }
}
