<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::firstOrCreate(
            ['email' => 'trgiahuy14@gmail.com'],
            [
                'name' => 'Customer',
                'email' => 'trgiahuy14@gmail.com',
                'password' => Hash::make('123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        Customer::firstOrCreate(
            ['email' => 'hr.demo@gmail.com'],
            [
                'name' => 'HR Demo',
                'email' => 'hr.demo@gmail.com',
                'password' => Hash::make('hrdemo'),
                'email_verified_at' => now(),
                'phone' => '0909000000',
                'avatar' => null,
                'points' => 0,
                'membership_tier' => 'standard',
                'is_active' => true,
            ]
        );
    }
}
