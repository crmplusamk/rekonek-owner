<?php

namespace Modules\Package\Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Package\App\Models\Package;

class PackageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('packages')->delete();

        $packages = [
            [
                "id" => Str::uuid(),
                "name" => "Free",
                "description" => "Paket dasar tanpa biaya untuk mencoba fitur-fitur Rekonek dan mulai mengelola kontak Anda. Ideal untuk pemula dan usaha kecil yang baru memulai.",
                "duration" => 1,
                "duration_type" => "month",
                "price" => null,
                "is_publish" => false,
                "order" => 1,
                "created_at" => now()
            ],
            [
                "id" => Str::uuid(),
                "name" => "Starter",
                "description" => "Paket untuk pemula yang membutuhkan fitur Rekonek lebih canggih dan integrasi tambahan.",
                "duration" => 1,
                "duration_type" => "month",
                "price" => 299000,
                "is_publish" => true,
                "order" => 3,
                "created_at" => now()
            ],
            [
                "id" => Str::uuid(),
                "name" => "Growth",
                "description" => "Paket lengkap untuk perusahaan dengan fitur Rekonek lebih canggih dan integrasi tambahan.",
                "duration" => 1,
                "duration_type" => "month",
                "price" => 499000,
                "is_publish" => true,
                "order" => 4,
                "created_at" => now()
            ],
            [
                "id" => Str::uuid(),
                "name" => "Business",
                "description" => "Paket untuk perusahaan dengan fitur Rekonek lebih canggih dan integrasi tambahan.",
                "duration" => 1,
                "duration_type" => "month",
                "price" => 799000,
                "is_publish" => true,
                "order" => 5,
                "created_at" => now()
            ]
        ];

        Package::insert($packages);
    }
}
