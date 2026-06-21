<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;
use Modules\Package\App\Models\Package;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration ini menambahkan katalog AI Credit:
     * 1. Feature `ai_credits` (child dari "Feature Dasar", is_addon=true)
     * 2. Addon "AI Credit" (1 unit = +1000 credit, charge=1000)
     * 3. package_feature per paket: Starter 0, Growth 5000, Business 10000
     *
     * Idempotent: cek existence sebelum insert.
     */
    public function up(): void
    {
        // Cek apakah feature AI Credit sudah ada (bisa dari seeder lama dengan key 'ai_credits')
        $aiCreditFeature = Feature::where('key', 'AICRD')->first()
            ?? Feature::where('key', 'ai_credits')->first();

        if ($aiCreditFeature) {
            // Feature sudah ada (mungkin dari seeder lama). Perbaiki data yang kurang.
            $basicParent = Feature::where('name', 'Feature Dasar')->first();

            DB::table('features')
                ->where('id', $aiCreditFeature->id)
                ->update([
                    'key' => 'AICRD',
                    'parent_id' => $aiCreditFeature->parent_id ?? $basicParent?->id,
                    'is_addon' => true,
                    'order' => $aiCreditFeature->order ?? 11,
                    'updated_at' => now(),
                ]);

            $featureId = $aiCreditFeature->id;
        } else {
            // Feature belum ada, insert baru
            $basicParent = Feature::where('name', 'Feature Dasar')->first();

            $featureId = (string) Str::uuid();

            DB::table('features')->insert([
                'id' => $featureId,
                'name' => 'AI Credit',
                'key' => 'AICRD',
                'parent_id' => $basicParent?->id,
                'is_parent' => false,
                'is_addon' => true,
                'order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert addon AI Credit jika belum ada: 1 unit = +1000 credit (charge=1000)
        $addonExists = DB::table('addons')->where('feature_id', $featureId)->first();

        if (! $addonExists) {
            DB::table('addons')->insert([
                'id' => (string) Str::uuid(),
                'feature_id' => $featureId,
                'name' => 'AI Credit',
                'description' => 'Tambahan credit AI.',
                'quantity' => 1,
                'charge' => 1000,
                'price' => 99000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert / update package_feature per paket
        $packages = Package::pluck('id', 'name')->toArray();

        $allocations = [
            'Starter' => 0,
            'Growth' => 5000,
            'Business' => 10000,
        ];

        foreach ($allocations as $pkgName => $limit) {
            if (! isset($packages[$pkgName])) {
                continue;
            }

            DB::table('package_feature')->updateOrInsert(
                [
                    'package_id' => $packages[$pkgName],
                    'feature_id' => $featureId,
                ],
                [
                    'visiblity' => true,
                    'included' => true,
                    'limit' => (string) $limit,
                    'limit_type' => 'max',
                    'updated_at' => now(),
                ],
            );
        }
    }
};
