<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@simateduh.com',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'POS Kasir Officer',
                'email' => 'kasir@simateduh.com',
                'password' => Hash::make('kasir123'),
                'role' => User::ROLE_KASIR,
            ],
            [
                'name' => 'Check-in Officer',
                'email' => 'checkin@simateduh.com',
                'password' => Hash::make('checkin123'),
                'role' => User::ROLE_CHECKIN,
            ],
            [
                'name' => 'Kasir Makanan',
                'email' => 'makan@simateduh.com',
                'password' => Hash::make('makan123'),
                'role' => User::ROLE_KASIR_MAKANAN,
            ],        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                    'remember_token' => Str::random(10),
                ],
            );
        }
    }
}
