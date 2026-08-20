<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['venue_id','name','position','active'];
    public function products() { return $this->hasMany(Product::class); }
}
