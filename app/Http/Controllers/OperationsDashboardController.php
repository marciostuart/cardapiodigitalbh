<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Tab;
use Illuminate\Http\Request;

class OperationsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);

        $tables = $venue->diningTables()
            ->with(['tabs' => fn ($query) => $query->where('status', 'open')])
            ->orderBy('code')
            ->get();

        $openTabs = Tab::query()->where('venue_id', $venue->id)->where('status', 'open');
        $todaySales = Order::query()
            ->where('venue_id', $venue->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        return view('operations.dashboard', [
            'venue' => $venue,
            'tables' => $tables,
            'openTabsCount' => (clone $openTabs)->count(),
            'occupiedTablesCount' => $tables->where('status', 'occupied')->count(),
            'todaySales' => $todaySales,
        ]);
    }
}
