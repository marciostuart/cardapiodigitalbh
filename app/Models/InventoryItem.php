<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['venue_id', 'name', 'sku', 'unit', 'stock_on_hand', 'stock_reserved', 'low_stock_threshold', 'active'];
    protected function casts(): array { return ['stock_on_hand' => 'decimal:3', 'stock_reserved' => 'decimal:3', 'low_stock_threshold' => 'decimal:3', 'active' => 'boolean']; }
    public function products() { return $this->belongsToMany(Product::class, 'product_recipes')->withPivot('quantity')->withTimestamps(); }
}
