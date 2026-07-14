<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToTenant;

class CustomerReward extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'customer_id',
        'reward_type',
        'reward_value',
        'description',
        'is_redeemed',
        'brand_id',
        'store_id',
    ];

    protected $casts = [
        'is_redeemed' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
