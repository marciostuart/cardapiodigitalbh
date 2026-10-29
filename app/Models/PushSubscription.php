<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'endpoint_hash', 'endpoint', 'public_key', 'auth_token', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'endpoint' => 'encrypted',
            'public_key' => 'encrypted',
            'auth_token' => 'encrypted',
            'last_used_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
