<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = ['tenant_id', 'name', 'subdomain', 'timezone', 'catalog_sort_mode', 'geofence_enabled', 'latitude', 'longitude', 'geofence_radius_meters', 'manual_open_state', 'manual_open_until', 'table_service_enabled', 'pickup_enabled', 'delivery_enabled', 'minimum_order', 'settings'];
    protected function casts(): array { return ['settings' => 'array', 'geofence_enabled' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'manual_open_until' => 'datetime', 'table_service_enabled' => 'boolean', 'pickup_enabled' => 'boolean', 'delivery_enabled' => 'boolean', 'minimum_order' => 'decimal:2']; }
    public function scheduleExceptions() { return $this->hasMany(VenueScheduleException::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function diningTables() { return $this->hasMany(DiningTable::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function schedules() { return $this->hasMany(VenueSchedule::class); }
    public function inventoryItems() { return $this->hasMany(InventoryItem::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function coupons() { return $this->hasMany(Coupon::class); }
    public function deliveryZones() { return $this->hasMany(DeliveryZone::class); }
}
