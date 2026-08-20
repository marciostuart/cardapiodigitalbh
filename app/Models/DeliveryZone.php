<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = ['venue_id', 'name', 'fee', 'neighborhoods', 'active'];
    protected function casts(): array { return ['fee' => 'decimal:2', 'active' => 'boolean']; }
}
