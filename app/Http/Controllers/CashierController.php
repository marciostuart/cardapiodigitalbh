<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Tab;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function pay(StorePaymentRequest $request, Tab $tab)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue && $tab->venue_id===$venue->id && $tab->status==='open',404);
        return DB::transaction(function() use($request,$tab,$venue){
            $tab=Tab::lockForUpdate()->findOrFail($tab->id);
            $due=$tab->orders()->whereNotIn('status',['cancelled'])->sum('total');
            $paid=$tab->payments()->where('status','confirmed')->sum('amount');
            $amount=(float)$request->validated('amount'); abort_if($amount > ((float)$due-(float)$paid+0.009),422,'Pagamento maior que o saldo da comanda.');
            $payment=$tab->payments()->create(['venue_id'=>$venue->id,'method'=>$request->validated('method'),'amount'=>$amount,'reference'=>$request->validated('reference'),'paid_at'=>now()]);
            if(($paid+$amount+0.009)>=$due){$tab->update(['status'=>'closed','closed_at'=>now()]);}
            return response()->json(['payment'=>$payment,'due'=>$due,'paid'=>$paid+$amount,'tab_status'=>$tab->fresh()->status],201);
        });
    }
}
