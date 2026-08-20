<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id','product_id','name_snapshot','unit_price','quantity','total','station','status','notes'];
    public function order() { return $this->belongsTo(Order::class); }
}
