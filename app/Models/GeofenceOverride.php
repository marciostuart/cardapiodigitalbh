<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeofenceOverride extends Model
{
    protected $fillable = ['tenant_id', 'venue_id', 'dining_table_id', 'created_by', 'code_hash', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }
}
