<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Basic Plan',
            'type' => 'basic',
            'price' => 100.00,
            'profit' => 10.00,
            'lock_time_in_days' => 30,
        ]);

        Plan::create([
            'name' => 'Standard Plan',
            'type' => 'standard',
            'price' => 200.00,
            'profit' => 25.00,
            'lock_time_in_days' => 60,
        ]);

        Plan::create([
            'name' => 'Premium Plan',
            'type' => 'premium',
            'price' => 500.00,
            'profit' => 75.00,
            'lock_time_in_days' => 90,
        ]);
    }
}
