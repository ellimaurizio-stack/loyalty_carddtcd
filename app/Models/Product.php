<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'brand_id',
        'name',
        'category',
        'ean_code',
        'price',
        'image_path',
        'stock',
    ];
}
