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
        $admin = User::query()->firstOrCreate([
            'name' => config('admin.name'),
            'email' => config(('admin.email')),
            'password' => Hash::make(config('admin.password')),
            'role' => 'admin'
        ]);
        dump($admin);
        User::factory()->count(20)->create();
    }
}
