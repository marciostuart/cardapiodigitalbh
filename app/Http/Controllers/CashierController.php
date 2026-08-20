<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);
        $tabs = Tab::where('venue_id', $venue->id)
            ->where('status', 'open')
            ->with(['diningTable', 'orders', 'payments'])
            ->latest('opened_at')
            ->get();

        return view('operations.cashier', compact('venue', 'tabs'));
    }

    public function pay(StorePaymentRequest $request, Tab $tab)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $tab->venue_id === $venue->id && $tab->status === 'open', 404);

        $result = DB::transaction(function () use ($request, $tab, $venue) {
            $tab = Tab::lockForUpdate()->findOrFail($tab->id);
            $due = $tab->orders()->whereNotIn('status', ['cancelled'])->sum('total');
            $paid = $tab->payments()->where('status', 'confirmed')->sum('amount');
            $amount = (float) $request->validated('amount');
            abort_if($amount > ((float) $due - (float) $paid + 0.009), 422, 'Pagamento maior que o saldo da comanda.');
            $payment = $tab->payments()->create(['venue_id' => $venue->id, 'method' => $request->validated('method'), 'amount' => $amount, 'reference' => $request->validated('reference'), 'paid_at' => now()]);
            if (($paid + $amount + 0.009) >= $due) {
                $tab->update(['status' => 'closed', 'closed_at' => now()]);
                $hasOtherOpenTabs = Tab::where('dining_table_id', $tab->dining_table_id)
                    ->where('status', 'open')
                    ->whereKeyNot($tab->id)
                    ->exists();
                if (! $hasOtherOpenTabs) {
                    $tab->diningTable?->update(['status' => 'available']);
                }
            }

            return ['payment' => $payment, 'due' => $due, 'paid' => $paid + $amount, 'tab_status' => $tab->fresh()->status];
        });

        return $request->expectsJson()
            ? response()->json($result, 201)
            : back()->with('status', 'Pagamento registrado com sucesso.');
    }
}
