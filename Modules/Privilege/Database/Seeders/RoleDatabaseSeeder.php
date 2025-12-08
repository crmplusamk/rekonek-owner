<?php

namespace Modules\Privilege\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = include(base_path('Modules/Privilege/Database/Resources/RoleSeederResource.php'));
        DB::table('roles')->insert($roles);
    }
}
