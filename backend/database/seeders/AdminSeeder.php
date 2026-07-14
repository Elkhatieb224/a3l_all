<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@a3lenha.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create Regular Admin
        Admin::create([
            'name' => 'أحمد محمد',
            'email' => 'ahmed@a3lenha.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create Moderator
        Admin::create([
            'name' => 'محمد علي',
            'email' => 'moderator@a3lenha.com',
            'password' => Hash::make('password123'),
            'role' => 'moderator',
            'is_active' => true,
        ]);
    }
}

