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
     * Migration ini melakukan:
     * 1. Rename feature名称 (MAU → Percakapan Pelanggan, dsb)
     * 2. Hapus 4 fitur yang dihapus dari paket (Management Komplain, Lead Scoring, Campaign Mgmt, Dedicated AM)
     * 3. Update limit List Building Starter (Terbatas = 500)
     * 4. Update limit Broadcast Pesan (100/hari | 1000/hari | Unlimited)
     * 5. Insert fitur baru Otomasi Pintar (belum ada di DB)
     */
    public function up(): void
    {
        // 1. Mapping old key → new name
        $renameMap = [
            'ABXQ1' => 'Percakapan Pelanggan (Unik) / Bln',
            'R7NDC' => 'Agent Rotator',
            'TSPQ8' => 'Template Chat Cepat',
            'WZ3PL' => 'Multi-Agent Inbox',
            'HMFGE' => 'List Building',
            'BCPES' => 'Broadcast Pesan',
            'HXLKS' => 'Support Channel',
            'ONBRD' => 'Onboarding',
        ];

        foreach ($renameMap as $key => $newName) {
            DB::table('features')
                ->where('key', $key)
                ->update(['name' => $newName]);
        }

        // 2. Hapus 4 fitur yang dihapus dari matriks paket
        //    Delete dari package_feature SEBELUM features (foreign key constraint)
        $deleteKeys = ['JXCHW', 'LDSCR', 'CMPMN', 'DMANR'];

        $featuresToDelete = Feature::whereIn('key', $deleteKeys)->get();
        foreach ($featuresToDelete as $feature) {
            // Hapus dari package_feature dulu ( FK constraint )
            DB::table('package_feature')
                ->where('feature_id', $feature->id)
                ->delete();

            // Hapus dari features
            DB::table('features')
                ->where('id', $feature->id)
                ->delete();
        }

        // 3. Ambil package IDs
        $packages = Package::pluck('id', 'name')->toArray();

        // 4. Feature IDs
        $featureIds = Feature::pluck('id', 'key')->toArray();

        // 5. Update limit List Building (Starter: Terbatas = 500)
        if (isset($featureIds['HMFGE']) && isset($packages['Starter'])) {
            DB::table('package_feature')
                ->where('feature_id', $featureIds['HMFGE'])
                ->where('package_id', $packages['Starter'])
                ->update([
                    'limit' => '500',
                    'limit_type' => 'max',
                ]);
        }

        // 6. Update limit Broadcast Pesan per paket
        // Starter: 100/hari, Growth: 1000/hari, Business: Unlimited (-1)
        if (isset($featureIds['BCPES'])) {
            $broadcastLimits = [
                'Starter' => 100,
                'Growth' => 1000,
                'Business' => -1, // unlimited
            ];

            foreach ($broadcastLimits as $pkgName => $limit) {
                if (!isset($packages[$pkgName])) {
                    continue;
                }

                DB::table('package_feature')
                    ->where('feature_id', $featureIds['BCPES'])
                    ->where('package_id', $packages[$pkgName])
                    ->update([
                        'limit' => (string) $limit,
                        'limit_type' => $limit === -1 ? null : 'max',
                    ]);
            }
        }

        // 7. Insert fitur baru: Otomasi Pintar
        $otomasiExists = Feature::where('key', 'OTMPR')->first();
        if (!$otomasiExists) {
            // Cari parent "Fitur Dasar"
            $basicParent = Feature::where('name', 'Feature Dasar')->first();

            $otonId = (string) Str::uuid();

            DB::table('features')->insert([
                'id' => $otonId,
                'name' => 'Otomasi Pintar',
                'key' => 'OTMPR',
                'parent_id' => $basicParent?->id,
                'is_parent' => false,
                'is_addon' => false,
                'order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert package_feature untuk Otomasi Pintar
            // Starter: 5 alur, Growth: 10 alur, Business: Unlimited
            $otomasiLimits = [
                'Starter' => 5,
                'Growth' => 10,
                'Business' => -1,
            ];

            foreach ($otomasiLimits as $pkgName => $limit) {
                if (!isset($packages[$pkgName])) continue;

                $limitType = $limit === -1 ? null : 'max';

                DB::table('package_feature')->insert([
                    'package_id' => $packages[$pkgName],
                    'feature_id' => $otonId,
                    'visiblity' => true,
                    'included' => true,
                    'limit' => (string) $limit,
                    'limit_type' => $limitType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};