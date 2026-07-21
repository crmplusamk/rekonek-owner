<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Feature\App\Models\Feature;
use Modules\Package\App\Models\Package;

/**
 * Menambahkan katalog "AI Agent":
 * 1. Feature `AI Agent` (key `AIAGT`, child "Feature Dasar", is_addon=true)
 * 2. Addon "AI Agent" (1 unit = +1 agent, charge=1, recurring)
 * 3. package_feature per paket: Free 1, Starter 3, Growth 5, Business unlimited (-1)
 * 4. Backfill snapshot `subscription_feature_rules` untuk langganan AKTIF agar company existing
 *    langsung mendapat limit (tanpa menunggu re-subscribe). Tanpa ini, resolver app tak menemukan
 *    aturan AIAGT di snapshot → middleware memblokir pembuatan AI Agent bagi subscriber lama.
 *
 * Idempotent: cek existence sebelum insert.
 */
return new class extends Migration
{
    public function up(): void
    {
        $basicParent = Feature::where('name', 'Feature Dasar')->first();

        // 1. Feature AI Agent
        $feature = Feature::where('key', 'AIAGT')->first();
        if ($feature) {
            $featureId = $feature->id;
            DB::table('features')->where('id', $featureId)->update([
                'name' => 'AI Agent',
                'parent_id' => $feature->parent_id ?? $basicParent?->id,
                'is_addon' => true,
                'order' => $feature->order ?? 12,
                'updated_at' => now(),
            ]);
        } else {
            $featureId = (string) Str::uuid();
            DB::table('features')->insert([
                'id' => $featureId,
                'name' => 'AI Agent',
                'key' => 'AIAGT',
                'parent_id' => $basicParent?->id,
                'is_parent' => false,
                'is_addon' => true,
                'order' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Addon AI Agent (1 unit = +1 agent). Harga default; sesuaikan bila perlu.
        if (! DB::table('addons')->where('feature_id', $featureId)->exists()) {
            $addon = [
                'id' => (string) Str::uuid(),
                'feature_id' => $featureId,
                'name' => 'AI Agent',
                'description' => 'Tambahan slot AI Agent.',
                'quantity' => 1,
                'charge' => 1,
                'price' => 99000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('addons', 'billing_type')) {
                $addon['billing_type'] = 'recurring';
            }
            DB::table('addons')->insert($addon);
        }

        // 3. package_feature per paket (Business unlimited = -1)
        $packages = Package::pluck('id', 'name')->toArray();
        $allocations = [
            'Free' => 1,
            'Starter' => 3,
            'Growth' => 5,
            'Business' => -1,
        ];

        foreach ($allocations as $pkgName => $limit) {
            if (! isset($packages[$pkgName])) {
                continue;
            }

            DB::table('package_feature')->updateOrInsert(
                ['package_id' => $packages[$pkgName], 'feature_id' => $featureId],
                [
                    'visiblity' => true,
                    'included' => true,
                    'limit' => (string) $limit,
                    'limit_type' => 'max',
                    'updated_at' => now(),
                ],
            );
        }

        // 4. Backfill snapshot langganan aktif (source=package) agar subscriber lama langsung dapat limit
        $activeSubs = DB::table('subscription_packages')
            ->where('is_active', true)
            ->get(['id', 'package_id', 'company_id']);

        foreach ($activeSubs as $subs) {
            $pf = DB::table('package_feature')
                ->where('package_id', $subs->package_id)
                ->where('feature_id', $featureId)
                ->first();

            if (! $pf) {
                continue; // paket tak dikenal / tanpa alokasi
            }

            $exists = DB::table('subscription_feature_rules')
                ->where('subscription_id', $subs->id)
                ->where('feature_id', $featureId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('subscription_feature_rules')->insert([
                'id' => (string) Str::uuid(),
                'subscription_id' => $subs->id,
                'feature_id' => $featureId,
                'company_id' => $subs->company_id,
                'limit' => $pf->limit,
                'limit_type' => $pf->limit_type,
                'included' => $pf->included,
                'visiblity' => $pf->visiblity,
                'source' => 'package',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $feature = Feature::where('key', 'AIAGT')->first();
        if (! $feature) {
            return;
        }

        DB::table('subscription_feature_rules')->where('feature_id', $feature->id)->delete();
        DB::table('package_feature')->where('feature_id', $feature->id)->delete();
        DB::table('addons')->where('feature_id', $feature->id)->delete();
        DB::table('features')->where('id', $feature->id)->delete();
    }
};
