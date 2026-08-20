<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = ['tenant_id', 'event', 'name', 'body', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
}
