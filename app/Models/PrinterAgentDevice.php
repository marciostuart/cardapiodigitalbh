<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterAgentDevice extends Model
{
    protected $fillable = ['tenant_id', 'venue_id', 'name', 'token_hash', 'is_primary', 'active', 'last_seen_at'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'active' => 'boolean', 'last_seen_at' => 'datetime'];
    }
}
