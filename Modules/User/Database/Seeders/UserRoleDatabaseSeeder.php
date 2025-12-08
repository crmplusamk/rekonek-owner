<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Privilege\App\Models\Role;
use Modules\User\App\Models\User;

class UserRoleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userAdmin = User::where('name', 'administrator')->first();
        $roleAdmin = Role::where('name', 'administrator')->first();
        $userAdmin->assignRole($roleAdmin);

        $userSales = User::where('name', 'staff')->first();
        $roleSales = Role::where('name', 'staff')->first();
        $userSales->assignRole($roleSales);
    }
}
