<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = ['tenant_id', 'name', 'subdomain', 'timezone', 'settings'];
    protected function casts(): array { return ['settings' => 'array']; }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function diningTables() { return $this->hasMany(DiningTable::class); }
    public function products() { return $this->hasMany(Product::class); }
}
