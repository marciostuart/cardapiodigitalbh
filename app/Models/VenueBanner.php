<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueBanner extends Model
{
    protected $fillable = ['venue_id', 'title', 'description', 'image_path', 'link_url', 'position', 'active', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
