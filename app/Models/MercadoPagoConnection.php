<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoConnection extends Model
{
    protected $fillable = ['tenant_id', 'seller_id', 'access_token', 'refresh_token', 'public_key', 'expires_at', 'connected_at'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
