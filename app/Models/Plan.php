<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'monthly_price', 'features', 'limits', 'active', 'position'];

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2', 'features' => 'array', 'limits' => 'array', 'active' => 'boolean'];
    }
}
