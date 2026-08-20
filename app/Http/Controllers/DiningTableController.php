<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiningTableRequest;
use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiningTableController extends Controller
{
    public function store(StoreDiningTableRequest $request)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue,404);
        $data=$request->validated(); $data['venue_id']=$venue->id; $data['qr_token']=Str::uuid();
        return response()->json(DiningTable::create($data),201);
    }
}
