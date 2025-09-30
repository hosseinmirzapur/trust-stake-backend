<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            $initialBalance = $wallet->balance;

            // Create deposit transactions
            $depositAmount = rand(100, 1000) + rand(0, 99) / 100;
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_DEPOSIT,
                'amount' => $depositAmount,
                'status' => Transaction::STATUS_COMPLETED,
                'balance_before' => $initialBalance,
                'balance_after' => $initialBalance + $depositAmount,
                'tx_hash' => Str::random(64),
            ]);

            // Create some payment transactions (for subscriptions)
            $paymentAmount = rand(50, 500) + rand(0, 99) / 100;
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_PAYMENT,
                'amount' => $paymentAmount,
                'status' => Transaction::STATUS_COMPLETED,
                'balance_before' => $initialBalance + $depositAmount,
                'balance_after' => $initialBalance + $depositAmount - $paymentAmount,
                'tx_hash' => Str::random(64),
            ]);

            // Create some pending withdrawal transactions
            $withdrawAmount = rand(25, 200) + rand(0, 99) / 100;
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_WITHDRAW,
                'amount' => $withdrawAmount,
                'status' => Transaction::STATUS_PENDING,
                'balance_before' => $initialBalance + $depositAmount - $paymentAmount,
                'balance_after' => $initialBalance + $depositAmount - $paymentAmount - $withdrawAmount,
                'tx_hash' => Str::random(64),
            ]);

            // Create some failed transactions
            $failedAmount = rand(10, 100) + rand(0, 99) / 100;
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_WITHDRAW,
                'amount' => $failedAmount,
                'status' => Transaction::STATUS_FAILED,
                'balance_before' => $initialBalance + $depositAmount - $paymentAmount,
                'balance_after' => $initialBalance + $depositAmount - $paymentAmount, // Balance unchanged for failed transaction
                'tx_hash' => Str::random(64),
            ]);
        }

        // Create some additional random transactions for variety
        $randomWallets = $wallets->random(min(5, $wallets->count()));
        foreach ($randomWallets as $wallet) {
            $currentBalance = $wallet->fresh()->balance;
            $randomAmount = rand(10, 300) + rand(0, 99) / 100;

            $transactionTypes = [Transaction::TYPE_DEPOSIT, Transaction::TYPE_PAYMENT];
            $randomType = $transactionTypes[array_rand($transactionTypes)];

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => $randomType,
                'amount' => $randomAmount,
                'status' => Transaction::STATUS_COMPLETED,
                'balance_before' => $currentBalance,
                'balance_after' => $randomType === Transaction::TYPE_DEPOSIT
                    ? $currentBalance + $randomAmount
                    : $currentBalance - $randomAmount,
                'tx_hash' => Str::random(64),
            ]);
        }
    }
}
