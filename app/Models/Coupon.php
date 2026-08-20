<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['venue_id', 'code', 'type', 'value', 'minimum_order', 'maximum_discount', 'usage_limit', 'used_count', 'starts_at', 'expires_at', 'active'];
    protected function casts(): array { return ['value' => 'decimal:2', 'minimum_order' => 'decimal:2', 'maximum_discount' => 'decimal:2', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'active' => 'boolean']; }
}
