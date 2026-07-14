<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // If user is Super Admin, they see everything
                if ($user->role === 'super_admin') {
                    return;
                }

                // If user is Brand Manager, filter by brand_id
                if ($user->role === 'brand_manager' && $user->brand_id) {
                    $builder->where($builder->getModel()->getTable() . '.brand_id', $user->brand_id);
                }

                // If user is Store Manager, filter by store_id if the model supports it
                if ($user->role === 'store_manager') {
                    $hasStoreId = method_exists($builder->getModel(), 'store') || array_key_exists('store_id', $builder->getModel()->getAttributes()) || $builder->getModel()->isFillable('store_id');
                    
                    if ($user->store_id && $hasStoreId) {
                        $builder->where($builder->getModel()->getTable() . '.store_id', $user->store_id);
                    } elseif ($user->brand_id) {
                        $builder->where($builder->getModel()->getTable() . '.brand_id', $user->brand_id);
                    }
                }
            }
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();

                if ($user->role === 'brand_manager' && $user->brand_id) {
                    if (empty($model->brand_id)) {
                        $model->brand_id = $user->brand_id;
                    }
                }

                if ($user->role === 'store_manager') {
                    if (empty($model->brand_id) && $user->brand_id) {
                        $model->brand_id = $user->brand_id;
                    }
                    
                    $hasStoreId = method_exists($model, 'store') || $model->isFillable('store_id');
                    if (empty($model->store_id) && $user->store_id && $hasStoreId) {
                        $model->store_id = $user->store_id;
                    }
                }
            }
        });
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class);
    }
}
