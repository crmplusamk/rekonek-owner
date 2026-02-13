<?php

namespace Modules\PromoCode\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PromoCode\App\Models\PromoCode;

class PromoCodeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'PROMO10'],
            [
                'name' => 'Promo 10%',
                'type' => 'registrasi_baru',
                'discount_type' => 'percentage',
                'discount_percentage' => 10,
                'discount_amount' => null,
                'min_purchase' => null,
                'max_discount' => 100000,
                'usage_limit' => 1000,
                'used_count' => 0,
                'per_user_limit' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'description' => 'Promo code diskon 10%',
            ]
        );
    }
}
