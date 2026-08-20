<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundMessage extends Model
{
    protected $fillable = ['tenant_id', 'order_id', 'channel', 'event', 'recipient', 'body', 'status', 'attempts', 'last_error', 'idempotency_key', 'sent_at'];
    protected function casts(): array { return ['recipient' => 'encrypted', 'body' => 'encrypted', 'sent_at' => 'datetime']; }
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
