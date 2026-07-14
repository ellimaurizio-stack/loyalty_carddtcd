<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class Purchase extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'customer_id',
        'amount',
        'loyalty_points_earned',
        'cashback_earned',
        'notes',
        'brand_id',
        'store_id',
        'products',
    ];

    protected $casts = [
        'products' => 'array',
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
