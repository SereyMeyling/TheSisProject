<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seeds one demo user per role.
     *
     * Passwords: taken from SEED_DEFAULT_PASSWORD in .env if set, otherwise a
     * random password is generated and printed once to the console.
     * Existing users keep their current password (re-running the seeder will
     * never reset a password that someone already changed).
     */
    public function run()
    {
        $usersData = [
            // NOTE: email kept as-is because existing installations log in with it.
            ['name' => 'Super Admin',     'username' => 'superadmin', 'email' => 'supperAdmin@gmai.com', 'role' => 'admin',      'department_id' => 1],
            ['name' => 'Doctor User',     'username' => 'doctor',     'email' => 'doctor@gmail.com',     'role' => 'doctor',     'department_id' => 2],
            ['name' => 'Nurse User',      'username' => 'nurse',      'email' => 'nurse@gmail.com',      'role' => 'nurse',      'department_id' => 1],
            ['name' => 'Pharmacist User', 'username' => 'pharmacist', 'email' => 'pharmacist@gmail.com', 'role' => 'pharmacist', 'department_id' => 9],
            ['name' => 'Cashier User',    'username' => 'cashier',    'email' => 'cashier@gmail.com',    'role' => 'cashier',    'department_id' => 1],
        ];

        $defaultPassword = env('SEED_DEFAULT_PASSWORD');

        foreach ($usersData as $data) {
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                $plain = $defaultPassword ?: Str::random(16);

                $user = new User();
                $user->email = $data['email'];
                $user->password = Hash::make($plain);

                if ($this->command) {
                    $this->command->info("Created {$data['email']} ({$data['role']}) with password: {$plain}");
                }
            }

            $user->name = $data['name'];
            $user->username = $data['username'];

            // Only link a department that actually exists
            if (Department::where('department_id', $data['department_id'])->exists()) {
                $user->department_id = $data['department_id'];
            }

            $user->save();
            $user->syncRoles([$data['role']]);
        }
    }
}
