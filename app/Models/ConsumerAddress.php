<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerAddress extends Model
{
    protected $fillable = ['consumer_account_id', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference', 'is_default'];

    public function consumerAccount()
    {
        return $this->belongsTo(ConsumerAccount::class);
    }
}
