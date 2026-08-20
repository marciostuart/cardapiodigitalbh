<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionInstance extends Model
{
    protected $fillable = ['tenant_id', 'instance_name', 'status', 'phone', 'last_error', 'connected_at', 'last_checked_at'];

    protected function casts(): array
    {
        return ['connected_at' => 'datetime', 'last_checked_at' => 'datetime'];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
