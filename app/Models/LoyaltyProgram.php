<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class LoyaltyProgram extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'brand_id',
        'name',
        'purchases_threshold',
        'is_active',
        'form_fields',
        'background_image_path',
        'background_color',
        'primary_color',
        'logo_path',
        'otp_channel',
        'otp_channel_label',
        'text_color',
        'translations'
    ];

    protected $casts = [
        'form_fields' => 'array',
        'translations' => 'array',
        'is_active' => 'boolean',
    ];

    public function disclaimers()
    {
        return $this->hasMany(Disclaimer::class);
    }

    public function rules()
    {
        return $this->hasMany(PromotionalRule::class);
    }
}
