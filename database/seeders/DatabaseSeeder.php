<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // <--- THIS IS THE EDIT (Needed to hash passwords)

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // <--- THIS IS THE EDIT (Below: Created the Admin Account)
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'admin', 
        ]);

        // <--- THIS IS THE EDIT (Below: Created the Member Account)
        User::create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => Hash::make('password123'), 
            // We leave 'type' blank here so the database uses the default ('member')
        ]);
    }
}
