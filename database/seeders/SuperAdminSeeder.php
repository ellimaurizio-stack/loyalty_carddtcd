<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brand = \App\Models\Brand::firstOrCreate(
            ['slug' => 'default-brand'],
            ['name' => 'Default Brand']
        );

        $store = \App\Models\Store::firstOrCreate(
            ['slug' => 'default-store'],
            [
                'brand_id' => $brand->id,
                'name' => 'Default Store',
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'admin@loyalty.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'brand_id' => null,
                'store_id' => null,
            ]
        );
    }
}
