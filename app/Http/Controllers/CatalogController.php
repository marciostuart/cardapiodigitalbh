<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function store(StoreProductRequest $request)
    {
        $venue=$request->attributes->get('venue'); abort_unless($venue,404);
        abort_unless(ProductCategory::where('id',$request->category_id)->where('venue_id',$venue->id)->exists(),422);
        $data=$request->validated(); $data['venue_id']=$venue->id; return response()->json(Product::create($data),201);
    }
}
