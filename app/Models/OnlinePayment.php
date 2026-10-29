<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlinePayment extends Model
{
    protected $fillable = ['tenant_id', 'order_id', 'external_reference', 'preference_id', 'provider_payment_id', 'amount', 'currency', 'status', 'provider_status', 'checkout_url', 'expires_at', 'paid_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
