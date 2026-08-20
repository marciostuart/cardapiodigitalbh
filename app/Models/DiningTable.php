<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    protected $fillable = ['venue_id','code','name','status','qr_token'];
    public function venue() { return $this->belongsTo(Venue::class); }
    public function tabs() { return $this->hasMany(Tab::class); }
}
