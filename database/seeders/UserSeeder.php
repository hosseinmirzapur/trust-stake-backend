<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'name' => env('DEFAULT_ADMIN_USER'),
            'email' => env('DEFAULT_ADMIN_EMAIL'),
            'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD')),
            'role' => 'admin'
        ]);
        User::factory()->count(20)->create();
    }
}
