<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed aturan diskon AI Credit (contoh awal — bisa diubah/tambah/hapus dari admin):
 *   >=5 blok  -> 90.000/blok (5 blok = 450.000)
 *   >=10 blok -> 80.000/blok (10 blok = 800.000, >10 = 80/credit)
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $aiCreditId = DB::table('addons')
            ->join('features', 'addons.feature_id', '=', 'features.id')
            ->where('features.key', 'AICRD')
            ->value('addons.id');

        if (! $aiCreditId) {
            return;
        }

        $tiers = [
            ['min' => 5, 'value' => 90000],
            ['min' => 10, 'value' => 80000],
        ];

        foreach ($tiers as $t) {
            $exists = DB::table('addon_price_tiers')
                ->where('addon_id', $aiCreditId)
                ->where('min_quantity', $t['min'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('addon_price_tiers')->insert([
                'id' => (string) Str::uuid(),
                'addon_id' => $aiCreditId,
                'min_quantity' => $t['min'],
                'type' => 'unit_price',
                'value' => $t['value'],
                'label' => 'Diskon',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $aiCreditId = DB::table('addons')
            ->join('features', 'addons.feature_id', '=', 'features.id')
            ->where('features.key', 'AICRD')
            ->value('addons.id');

        if ($aiCreditId) {
            DB::table('addon_price_tiers')->where('addon_id', $aiCreditId)->delete();
        }
    }
};
