<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderItemStatusRequest;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class KitchenDisplayController extends Controller
{
    public function index(Request $request)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue,404);
        return Order::where('venue_id',$venue->id)->whereIn('status',['pending','accepted','preparing'])->with('items')->latest()->get();
    }
    public function update(UpdateOrderItemStatusRequest $request, OrderItem $item)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue && $item->order->venue_id===$venue->id,404);
        $item->update(['status'=>$request->validated('status')]); return response()->json($item);
    }
}
