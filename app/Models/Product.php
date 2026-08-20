<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['venue_id','product_category_id','name','sku','description','price','track_stock','stock_on_hand','stock_reserved','low_stock_threshold','image_path','station','active','position'];
    protected function casts(): array { return ['price' => 'decimal:2', 'track_stock' => 'boolean', 'stock_on_hand' => 'decimal:3', 'stock_reserved' => 'decimal:3', 'low_stock_threshold' => 'decimal:3', 'active' => 'boolean']; }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
    public function ingredients() { return $this->belongsToMany(InventoryItem::class, 'product_recipes')->withPivot('quantity')->withTimestamps(); }
}
