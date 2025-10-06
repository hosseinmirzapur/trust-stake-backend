<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'name' => env('DEFAULT_ADMIN_USER'),
            'email' => env('DEFAULT_ADMIN_EMAIL'),
            'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD')),
            'role' => 'admin'
        ]);
        dump($admin);
        User::factory()->count(20)->create();
    }
}
