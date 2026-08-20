<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id','product_id','name_snapshot','unit_price','quantity','total','station','status','notes','options_snapshot'];
    protected function casts(): array { return ['options_snapshot' => 'array', 'unit_price' => 'decimal:2', 'total' => 'decimal:2']; }
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
