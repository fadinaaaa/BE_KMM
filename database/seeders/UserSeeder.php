<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'), // password harus di-hash
            'role'     => 'admin'
        ]);

        User::create([
            'username' => 'user1',
            'password' => Hash::make('password123'),
            'role'     => 'user'
        ]);
    }
}
