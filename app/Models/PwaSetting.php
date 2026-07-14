<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PwaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
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
