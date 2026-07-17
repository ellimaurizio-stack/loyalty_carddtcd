<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PwaSetting extends Model
{
    use BelongsToTenant;

    use HasFactory;

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    protected $fillable = [
        'brand_id',
        'store_id',
        'app_name',
        'primary_color',
        'background_color',
        'text_color',
        'card_color',
        'card_text_color',
        'logo_path',
        'background_image_path',
        'registration_fields',
        'privacy_policy',
    ];

    protected $casts = [
        'registration_fields' => 'array',
    ];
}
