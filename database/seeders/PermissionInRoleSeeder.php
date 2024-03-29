<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionInRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //permission assign to dlc
        $dlc = Role::whereName('Admin')->first();
        $dlc->syncPermissions(
            [
                'user_access',
                'role_access',
            ]
        );
    }
}
