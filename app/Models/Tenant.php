<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'status'];
    public function venues() { return $this->hasMany(Venue::class); }
}
