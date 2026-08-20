<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'plan', 'plan_expires_at'];

    protected function casts(): array
    {
        return ['plan_expires_at' => 'date'];
    }

    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function evolutionInstance()
    {
        return $this->hasOne(EvolutionInstance::class);
    }
}
