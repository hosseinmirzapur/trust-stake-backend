<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Skip if user already has a wallet
            if ($user->wallet) {
                continue;
            }

            // Create wallet with random balance between 100 and 5000 USDT
            // Users only possess USDT not any other coins
            Wallet::create([
                'user_id' => $user->id,
                'balance' => rand(100, 5000) + rand(0, 99) / 100, // Random balance with decimals
                'currency' => 'USDT',
            ]);
        }
    }
}
