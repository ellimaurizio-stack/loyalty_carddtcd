<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PromotionalRule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'brand_id',
        'loyalty_program_id',
        'name',
        'type',
        'is_active',
        'parameters',
        'conditions',
        'priority',
        'is_stackable',
        'image_path'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
        'parameters' => 'array',
        'conditions' => 'array',
    ];

    public function loyaltyProgram()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
