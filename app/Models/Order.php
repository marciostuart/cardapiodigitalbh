<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['venue_id', 'tab_id', 'channel', 'status', 'station', 'subtotal', 'total', 'notes'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tab()
    {
        return $this->belongsTo(Tab::class);
    }
}
