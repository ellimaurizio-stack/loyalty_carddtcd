<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\BelongsToTenant;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'card_identifier',
        'name',
        'surname',
        'email',
        'phone',
        'dob',
        'privacy_accepted_at',
        'loyalty_points',
        'cashback_balance',
        'customer_data',
        'extra_data',
        'accepted_disclaimers',
        'password',
        'brand_id',
        'registration_store_id',
        'recency_score',
        'frequency_score',
        'monetary_score',
        'rfm_segment',
        'rfm_previous_segment',
        'rfm_updated_at',
    ];

    protected $casts = [
        'customer_data' => 'array',
        'extra_data' => 'array',
        'accepted_disclaimers' => 'array',
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
