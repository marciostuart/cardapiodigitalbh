<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = ['product_option_group_id', 'name', 'price', 'position', 'active'];
    protected function casts(): array { return ['price' => 'decimal:2', 'active' => 'boolean']; }
    public function group() { return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_id'); }
}
