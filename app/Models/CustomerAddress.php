<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference'];
}
