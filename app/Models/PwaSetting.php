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
        'logo_path',
    ];
}
