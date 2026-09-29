<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['name' => 'Gega',   'email' => 'gegagagua@gmail.com', 'password' => 'password'],
            ['name' => 'Admin 2', 'email' => 'admin2@example.com',  'password' => 'password'],
            ['name' => 'Admin 3', 'email' => 'admin3@example.com',  'password' => 'password'],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['password']),
                ]
            );
        }
    }
}
