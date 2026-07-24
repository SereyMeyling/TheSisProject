<?php

namespace Database\Seeders;

use App\Models\Setting\GeneralSettings;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GeneralSettings::updateOrCreate(
            ['id' => 1],
            [
                'system_name' => 'Sunrise Clinic',
                'phone' => '+855 12 345 678',
                'email' => 'contact@sunriseclinic.com',
                'address' => 'St. 271, Phnom Penh, Cambodia',
                'facebook' => 'https://facebook.com/sunriseclinic',
                'telegram' => 'https://t.me/sunriseclinic',
                'working_hours' => 'ចន្ទ-សុក្រ 8:00AM - 5:00PM',
                'logo' => 'https://via.placeholder.com/150?text=Logo',
                'favicon' => 'https://via.placeholder.com/32?text=Fav',
            ]
        );
    }
}
