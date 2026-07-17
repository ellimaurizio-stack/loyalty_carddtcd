<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class AppSetting extends Model
{
    use HasFactory, BelongsToTenant;

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
