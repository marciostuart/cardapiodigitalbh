<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['venue_id', 'inventory_item_id', 'product_id', 'order_id', 'actor_id', 'type', 'quantity', 'balance_after', 'notes'];
}
