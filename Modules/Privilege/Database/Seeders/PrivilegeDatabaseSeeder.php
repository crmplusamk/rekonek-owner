<?php

namespace Modules\Privilege\Database\Seeders;

use Illuminate\Database\Seeder;

class PrivilegeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleDatabaseSeeder::class
        ]);
    }
}
