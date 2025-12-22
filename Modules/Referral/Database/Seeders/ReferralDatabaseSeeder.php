<?php

namespace Modules\Referral\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Referral\App\Models\Referral;

class ReferralDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default referral codes
        $referrals = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Referral Code',
                'discount_type' => 'percentage',
                'discount_percentage' => 10,
                'discount_amount' => null,
                'min_purchase' => 0,
                'max_discount' => 100000,
                'usage_limit' => 1000,
                'used_count' => 0,
                'per_user_limit' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'description' => 'Kode referral dengan diskon 10% untuk pengguna baru',
            ],
            [
                'code' => 'FIRST50K',
                'name' => 'First Purchase Discount',
                'discount_type' => 'nominal',
                'discount_percentage' => null,
                'discount_amount' => 50000,
                'min_purchase' => 200000,
                'max_discount' => null,
                'usage_limit' => 500,
                'used_count' => 0,
                'per_user_limit' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'description' => 'Potongan langsung Rp 50.000 untuk pembelian minimal Rp 200.000',
            ],
        ];

        foreach ($referrals as $referralData) {
            Referral::updateOrCreate(
                ['code' => $referralData['code']],
                $referralData
            );
        }

        $this->command->info('Referral codes seeded successfully!');
    }
}
