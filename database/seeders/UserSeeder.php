<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default_password = Hash::make('123');

        // Super Admin
        User::firstorCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'password' => $default_password,
                'email_verified_at' => now(),
                'department_id' => 1,
                'position_id' => 1,
                'team_id' => 1,
                'employment_type' => 0,
                'status' => 0,
                'start_date' => now(),
                'end_date' => null,
                'gender' => 0,
                'birthday' => '2003-10-14',
                'phone' => '0987654321',
                'address' => 'Hồ Chí Minh, Việt Nam',
            ]
        );

        // Admin
        User::firstorCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => $default_password,
                'email_verified_at' => now(),
                'department_id' => 1,
                'position_id' => 1,
                'team_id' => 1,
                'employment_type' => 0,
                'status' => 0,
                'start_date' => now(),
                'end_date' => null,
                'gender' => 0,
                'birthday' => '2003-10-14',
                'phone' => '0987654321',
                'address' => 'Hội An, Việt Nam',
            ]
        );

        // Manager
        User::firstOrCreate(
            ['email' => 'manager@gmail.com'],
            [
                'name' => 'Manager',
                'password' => $default_password,
                'email_verified_at' => now(),
                'department_id' => 2,
                'position_id' => 2,
                'team_id' => 1,
                'employment_type' => 0,
                'status' => 0,
                'start_date' => now(),
                'end_date' => null,
                'gender' => 1,
                'birthday' => '1992-05-20',
                'phone' => '0902000000',
                'address' => 'Cần Thơ, Việt Nam',
            ]
        );

        User::firstOrCreate(
            ['email' => 'employee@gmail.com'],
            [
                'name' => 'Employee',
                'password' => $default_password,
                'email_verified_at' => now(),
                'department_id' => 2,
                'position_id' => 3,
                'team_id' => 3,
                'employment_type' => 0,
                'status' => 0,
                'start_date' => now(),
                'end_date' => null,
                'gender' => 0,
                'birthday' => '2001-10-15',
                'phone' => '0903000000',
                'address' => 'Hà Nội, Việt Nam',
            ]
        );

        User::firstOrCreate(
            ['email' => 'hr.demo@gmail.com'],
            [
                'name' => 'HR Demo',
                'email' => 'hr.demo@gmail.com',
                'password' => Hash::make('hrdemo'),
                'email_verified_at' => now(),
                'department_id' => 1,
                'position_id' => 1,
                'team_id' => 1,
                'employment_type' => 0,
                'status' => 0,
                'start_date' => now(),
                'end_date' => null,
                'gender' => 0,
                'birthday' => '1998-01-01',
                'phone' => '0909000000',
                'address' => 'Hồ Chí Minh, Việt Nam',
            ]
        );

        User::factory()->count(15)->create();
    }
}
