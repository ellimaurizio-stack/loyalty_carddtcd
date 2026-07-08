<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'ean_code',
        'price',
        'image_path',
        'stock',
    ];
}
