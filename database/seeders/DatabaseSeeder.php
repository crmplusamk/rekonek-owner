<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Contact\Database\Seeders\ContactDatabaseSeeder;
use Modules\Feature\Database\Seeders\FeatureDatabaseSeeder;
use Modules\Package\Database\Seeders\PackageDatabaseSeeder;
use Modules\Package\Database\Seeders\PackageFeatureDatabaseSeeder;
use Modules\Privilege\Database\Seeders\PrivilegeDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PrivilegeDatabaseSeeder::class,
            UserDatabaseSeeder::class,
            FeatureDatabaseSeeder::class,
            PackageDatabaseSeeder::class,
            PackageFeatureDatabaseSeeder::class,
            ContactDatabaseSeeder::class,
            // PackageFeatureSeeder::class,
            // AddonSeeder::class,
        ]);
    }
}
