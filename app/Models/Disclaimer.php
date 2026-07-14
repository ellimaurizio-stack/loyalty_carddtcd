<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Disclaimer extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'brand_id',
        'loyalty_program_id',
        'text',
        'is_mandatory',
        'pdf_path',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function loyaltyProgram()
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
