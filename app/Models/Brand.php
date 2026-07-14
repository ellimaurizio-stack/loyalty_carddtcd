<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'logo_path'];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
