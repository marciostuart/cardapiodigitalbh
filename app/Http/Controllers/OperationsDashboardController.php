<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperationsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue,404);
        return view('operations.dashboard',['venue'=>$venue,'tables'=>\App\Models\DiningTable::where('venue_id',$venue->id)->with(['tabs'=>fn($q)=>$q->where('status','open')])->orderBy('code')->get()]);
    }
}
