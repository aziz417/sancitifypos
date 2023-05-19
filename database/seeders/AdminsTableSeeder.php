<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $super_admin1 = User::query()->create([
            'name' => 'Hadisur Rahman',
            'first_name' => 'Hadisur',
            'last_name' => 'Rahman',
            'username' => 'hudacse6',
            'status' => 'active',
            'email' => 'hudacse6@gmail.com',
            'phone' => '+8801745969697',
            'password' => 'password',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $super_admin2 = User::query()->create([
            'name' => 'Zaheer Gilani',
            'first_name' => 'Zaheer',
            'last_name' => 'Gilani',
            'username' => 'zaheer',
            'status' => 'active',
            'email' => 'superadmin2@gmail.com',
            'phone' => null,
            'password' => 'password',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $super_admin1->assignRole('super-admin');
        $super_admin2->assignRole('super-admin');
    }
}
