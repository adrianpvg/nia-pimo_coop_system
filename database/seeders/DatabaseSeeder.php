<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; 

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the Admin Account (Active & Verified)
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'admin', 
            'is_active' => true, // <-- EDIT: Allow immediate login
            'email_verified_at' => now(), // <-- EDIT: Skip email verification step
        ]);

        // 2. Create the Member Account (Active & Verified)
        User::create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password123'), 
            'type' => 'member', 
            'is_active' => true, // <-- EDIT: Allow immediate login
            'email_verified_at' => now(), // <-- EDIT: Skip email verification step
        ]);

        // 3. Create Offices
        Office::create(['name' => 'PIMO']);
        Office::create(['name' => 'ADRIS']);
        Office::create(['name' => 'LARIS']);
        Office::create(['name' => 'R1']);
    }
}