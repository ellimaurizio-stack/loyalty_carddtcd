<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'bg_color',
        'header_color',
        'header_text',
        'header_text_color',
        'pay_btn_color',
        'pay_btn_text',
        'pay_btn_text_color',
        'cart_icon_color',
        'logo_path',
        'background_image_path',
    ];
}
