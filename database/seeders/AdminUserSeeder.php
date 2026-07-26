<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@hospital.com'
            ],
            [
                'name' => 'Administrator',
                'password' => Hash::make('areyouadmin'),
            ]
        );

        // Assign admin role
        $admin->assignRole('admin');
    }
}
