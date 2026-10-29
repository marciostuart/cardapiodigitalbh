<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsAcceptance extends Model
{
    protected $fillable = ['terms_version', 'privacy_version', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
