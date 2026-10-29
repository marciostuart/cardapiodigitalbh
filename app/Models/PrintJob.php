<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = ['tenant_id', 'venue_id', 'order_id', 'printer_agent_device_id', 'kind', 'job_key', 'payload', 'status', 'attempts', 'last_error', 'printer_name', 'claimed_at', 'printed_at'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'claimed_at' => 'datetime', 'printed_at' => 'datetime'];
    }
}
