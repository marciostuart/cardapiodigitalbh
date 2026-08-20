<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['venue_id','tab_id','method','amount','status','reference','paid_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'datetime']; }
}
