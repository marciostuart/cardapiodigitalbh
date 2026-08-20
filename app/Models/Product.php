<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['venue_id','product_category_id','name','description','price','image_path','active','position'];
}
