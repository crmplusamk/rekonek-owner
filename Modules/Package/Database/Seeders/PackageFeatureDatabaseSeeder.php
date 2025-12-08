<?php

namespace Modules\Package\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Feature\App\Models\Feature;
use Modules\Package\App\Models\Package;

class PackageFeatureDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('package_feature')->delete();

        $limit = [
            [
                [true, true, 1, "max"],      // Free
                [true, true, 2, "max"],      // Starter
                [true, true, 5, "max"],      // Growth
                [true, true, 10, "max"],     // Business
            ],
            [
                [true, true, 500, "max"],    // Free
                [true, true, 1000, "max"],   // Starter
                [true, true, 5000, "max"],   // Growth
                [true, true, 10000, "max"],   // Business
            ],
            [
                [true, true, 1, "max"],      // Free
                [true, true, 5, "max"],      // Starter
                [true, true, 10, "max"],     // Growth
                [true, true, -1, null],      // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, -1, null],      // Growth
                [true, true, -1, null],      // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, false, null, null],   // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],     // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, false, null, null],   // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, false, null, null],   // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, true, "Chat", null],  // Starter
                [true, true, "Chat & Call", null], // Growth
                [true, true, "Priority", null], // Business
            ],
            [
                [true, true, null, null],    // Free
                [true, true, null, null],    // Starter
                [true, true, null, null],    // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, false, null, null],   // Starter
                [true, false, null, null],   // Growth
                [true, true, null, null],    // Business
            ],
            [
                [true, false, null, null],   // Free
                [true, false, null, null],   // Starter
                [true, false, null, null],   // Growth
                [true, true, null, null],    // Business
            ],
        ];

        $features = Feature::where('is_parent', false)->get();
        $packages = Package::get();

        foreach($packages as $k => $package)
        {
            foreach($features as $j => $feature)
            {
                DB::table('package_feature')->insert([
                    'package_id' => $package->id,
                    'feature_id' => $feature->id,
                    'visiblity' => $limit[$j][$k][0],
                    'included' => $limit[$j][$k][1],
                    'limit' => $limit[$j][$k][2],
                    'limit_type' => $limit[$j][$k][3],
                ]);
            }
        }
    }
}
