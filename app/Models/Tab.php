<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    protected $fillable = ['venue_id','dining_table_id','code','status','service_charge_rate','opened_at','closed_at'];
    protected function casts(): array { return ['opened_at'=>'datetime','closed_at'=>'datetime','service_charge_rate'=>'decimal:2']; }
    public function orders() { return $this->hasMany(Order::class); }
}
