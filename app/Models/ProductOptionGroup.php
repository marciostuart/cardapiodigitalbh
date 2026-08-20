<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionGroup extends Model
{
    protected $fillable = ['product_id', 'name', 'minimum', 'maximum', 'position', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function options() { return $this->hasMany(ProductOption::class)->orderBy('position')->orderBy('name'); }
}
