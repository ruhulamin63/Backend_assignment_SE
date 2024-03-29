<?php

namespace Database\Seeders;

use App\Models\DetailsUser;
use App\Models\RtoUser;
use App\Models\User;
use App\Models\EnrollUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Super-Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
        ]);
        $admin->syncRoles(['Admin']);
    }
}
