<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = include(base_path('Modules/User/Database/Resources/UserSeederResource.php'));
        DB::table('users')->insert($users);
    }
}
