<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'card_identifier',
        'name',
        'surname',
        'email',
        'phone',
        'loyalty_points',
        'cashback_balance',
        'customer_data',
    ];

    protected $casts = [
        'customer_data' => 'array',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }
}
