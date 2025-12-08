<?php

namespace Modules\Feature\Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Addon\App\Models\Addon;
use Modules\Feature\App\Models\Feature;

class FeatureDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('features')->delete();

        $features = [
            [
                'name' => 'Feature Dasar',
                'is_parent' => true,
                'order' => 1,
                'childs' => [
                    [
                        'key' => '7ZVLY',
                        'name' => 'Jumlah Nomor Whatsapp',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 1,
                    ],
                    [
                        'key' => 'ABXQ1',
                        'name' => 'Monthly Active User (MAU)',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 2,
                    ],
                    [
                        'key' => 'K9MHT',
                        'name' => 'CS Agent',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 3,
                    ],
                    [
                        'key' => 'WZ3PL',
                        'name' => 'Multi Agent Inbox',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 4,
                    ],
                    [
                        'key' => 'R7NDC',
                        'name' => 'Rotator',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 5,
                    ],
                    [
                        'key' => 'TSPQ8',
                        'name' => 'Template Chat Tepat',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 6,
                    ],
                    [
                        'key' => 'STJ2L',
                        'name' => 'Pesan Terjadwal',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 7,
                    ],
                    [
                        'key' => 'HMFGE',
                        'name' => 'List Building',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 8,
                    ],
                    [
                        'key' => 'JXCHW',
                        'name' => 'Management Komplain',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 9,
                    ],
                ],
            ],
        
            // Manajemen Lead
            [
                'name' => 'Manajemen Lead',
                'is_parent' => true,
                'order' => 2,
                'childs' => [
                    [
                        'key' => 'PPOJS',
                        'name' => 'Pipeline Lead Tracking',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 1,
                    ],
                    [
                        'key' => 'JRLDT',
                        'name' => 'Reminder & Follow Up',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 2,
                    ],
                    [
                        'key' => 'LDSCR',
                        'name' => 'Lead Scoring',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 3,
                    ],
                    [
                        'key' => 'CPSTG',
                        'name' => 'Custom Pipeline Stages',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 4,
                    ],
                    [
                        'key' => 'GFGHG',
                        'name' => 'Sales Target',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 5,
                    ],
                ],
            ],
        
            // Broadcast Marketing
            [
                'name' => 'Broadcast Marketing',
                'is_parent' => true,
                'order' => 3,
                'childs' => [
                    [
                        'key' => 'BCPES',
                        'name' => 'Broadcast Pesan',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 1,
                    ],
                    [
                        'key' => 'CMPMN',
                        'name' => 'Campaign Management',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 2,
                    ],
                ],
            ],
        
            // Support & Training
            [
                'name' => 'Support & Training',
                'is_parent' => true,
                'order' => 4,
                'childs' => [
                    [
                        'key' => 'HXLKS',
                        'name' => 'Support Channel',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 1,
                    ],
                    [
                        'key' => 'ONBRD',
                        'name' => 'Onboarding',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 2,
                    ],
                    [
                        'key' => 'DMANR',
                        'name' => 'Dedicated Account Manager',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 3,
                    ],
                    [
                        'key' => 'CTRNG',
                        'name' => 'Custom Training',
                        'is_parent' => false,
                        'is_addon' => false,
                        'order' => 4,
                    ],
                ],
            ],
        ];

        foreach($features as $feature)
        {
            $parent = Feature::create([
                "id" => Str::uuid(),
                "name" => $feature['name'],
                "key" => $feature['key'] ?? null,
                "parent_id" => null,
                "is_parent" => isset($feature['key']) ? false : true,
                "order" => $feature['order']
            ]);

            if (!isset($feature['childs'])) continue;

            foreach($feature['childs'] as $child)
            {
                $feature = Feature::create([
                    "id" => Str::uuid(),
                    "name" => $child['name'],
                    "key" => $child['key'],
                    "parent_id" => $parent->id,
                    "is_parent" => false,
                    "is_addon" => $child['is_addon'],
                    "order" => $child['order']
                ]);

                if (isset($child['addon']))
                {
                    Addon::create([
                        "id" => Str::uuid(),
                        "feature_id" => $feature->id,
                        "name" => $child['addon']['name'],
                        "description" => $child['addon']['description'],
                        "charge" => $child['addon']['charge'],
                        "price" => $child['addon']['price'],
                        "is_active" => $child['addon']['is_active'],
                    ]);
                }
            }
        }
    }
}
