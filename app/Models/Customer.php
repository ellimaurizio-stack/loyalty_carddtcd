<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'cashback_balance' => 'decimal:2',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
