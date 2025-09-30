<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->take(3);
        $plans = Plan::all();

        foreach ($users as $index => $user) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plans[$index]->id,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 'active',
            ]);
        }
    }
}
