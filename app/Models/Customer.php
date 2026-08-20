<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['venue_id', 'name', 'phone', 'email', 'marketing_consent', 'last_order_at'];
    protected function casts(): array { return ['marketing_consent' => 'boolean', 'last_order_at' => 'datetime']; }
    public function addresses() { return $this->hasMany(CustomerAddress::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
