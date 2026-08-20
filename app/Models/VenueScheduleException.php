<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueScheduleException extends Model
{
    protected $fillable = ['venue_id', 'date', 'is_closed', 'opens_at', 'closes_at', 'note'];
    protected function casts(): array { return ['date' => 'date', 'is_closed' => 'boolean']; }
    public function venue() { return $this->belongsTo(Venue::class); }
}
