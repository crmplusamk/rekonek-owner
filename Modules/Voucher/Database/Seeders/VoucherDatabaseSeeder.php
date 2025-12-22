<?php

namespace Modules\Voucher\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Voucher\App\Models\Voucher;

class VoucherDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default vouchers
        $vouchers = [
            [
                'code' => 'FIRST50',
                'name' => 'First Time Discount',
                'discount_type' => 'nominal',
                'discount_amount' => 50000,
                'min_purchase' => 100000,
                'usage_limit' => 50,
                'per_user_limit' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'description' => 'Voucher diskon untuk pembelian pertama',
            ],
            [
                'code' => 'DISCOUNT20',
                'name' => '20% Discount Voucher',
                'discount_type' => 'percentage',
                'discount_percentage' => 20,
                'min_purchase' => 200000,
                'max_discount' => 100000,
                'usage_limit' => 100,
                'per_user_limit' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'description' => 'Voucher diskon 20% dengan maksimal diskon 100rb',
            ],
        ];

        foreach ($vouchers as $voucherData) {
            Voucher::updateOrCreate(
                ['code' => $voucherData['code']],
                $voucherData
            );
        }
    }
}
