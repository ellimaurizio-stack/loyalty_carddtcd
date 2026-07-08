<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reward_type',
        'reward_value',
        'description',
        'is_redeemed',
    ];

    protected $casts = [
        'is_redeemed' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
