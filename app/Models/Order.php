<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['venue_id', 'tab_id', 'customer_id', 'delivery_zone_id', 'public_token', 'channel', 'status', 'station', 'customer_name', 'customer_phone', 'whatsapp_consent', 'delivery_address', 'coupon_code', 'discount', 'delivery_fee', 'payment_method', 'change_for', 'subtotal', 'total', 'notes'];

    protected function casts(): array
    {
        return ['delivery_address' => 'array', 'discount' => 'decimal:2', 'delivery_fee' => 'decimal:2', 'change_for' => 'decimal:2', 'subtotal' => 'decimal:2', 'total' => 'decimal:2', 'whatsapp_consent' => 'boolean'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tab()
    {
        return $this->belongsTo(Tab::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
