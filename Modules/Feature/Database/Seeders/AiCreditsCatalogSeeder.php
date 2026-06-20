<?php

namespace Modules\Feature\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Addon\App\Models\Addon;
use Modules\Feature\App\Models\Feature;
use Modules\Package\App\Models\Package;

/**
 * Seeder idempotent katalog AI Credit (lihat prd-ai-credit-billing-integration.md):
 * - Feature `ai_credits`: sumber `limit` cycle credit yang dibaca rekonek lewat auth API
 *   (GET /api/authentication/{company_id}) dan disimpan di session rules.
 * - package_feature: Starter limit 0, Growth 5000, Business 10000 (semua `included=true` agar
 *   addon dapat melipat & memberi akses; Starter tanpa cycle credit, hanya via addon).
 * - Addon `AI Credit`: 1 unit menambah 1000 ke limit (charge=1000), mengikuti mekanisme fold
 *   owner: limit_efektif = package_feature.limit + subscriptionAddons.charge.
 *
 * Aman dijalankan berulang (updateOrCreate / updateOrInsert), tidak menghapus data lain.
 * Jalankan: php artisan db:seed --class="Modules\\Feature\\Database\\Seeders\\AiCreditsCatalogSeeder"
 */
class AiCreditsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $feature = Feature::updateOrCreate(
            ['key' => 'ai_credits'],
            [
                'name' => 'AI Credit',
                'is_parent' => false,
                'is_addon' => true,
                'parent_id' => null,
            ],
        );

        // 1 unit addon = +1000 credit ke limit. Charge per unit = 1000 (pola sama dengan addon MAU).
        Addon::updateOrCreate(
            ['feature_id' => $feature->id],
            [
                'name' => 'AI Credit',
                'description' => 'Tambahan credit AI untuk auto-reply. 1 unit = 1000 credit.',
                'quantity' => 1,
                'charge' => 1000,
                'price' => (int) env('AI_CREDIT_ADDON_PRICE', 99000), // TODO: harga final oleh tim billing
                'is_active' => true,
            ],
        );

        // Alokasi cycle credit per paket. Starter 0 (akses AI hanya via addon).
        $allocations = [
            'Starter' => 0,
            'Growth' => 5000,
            'Business' => 10000,
        ];

        foreach ($allocations as $packageName => $limit) {
            $package = Package::where('name', $packageName)->first();
            if (! $package) {
                $this->command?->warn("AiCreditsCatalogSeeder: paket '{$packageName}' tidak ditemukan, dilewati.");
                continue;
            }

            DB::table('package_feature')->updateOrInsert(
                ['package_id' => $package->id, 'feature_id' => $feature->id],
                [
                    'visiblity' => true,
                    'included' => true,
                    'limit' => $limit,
                    'limit_type' => 'max',
                ],
            );
        }

        $this->command?->info('AiCreditsCatalogSeeder: feature ai_credits + addon AI Credit + package_feature tersimpan.');
    }
}
