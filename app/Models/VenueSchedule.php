<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueSchedule extends Model
{
    protected $fillable = ['venue_id', 'day_of_week', 'opens_at', 'closes_at', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
}
