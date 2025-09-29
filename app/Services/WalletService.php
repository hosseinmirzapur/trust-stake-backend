<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    /**
     * @return array
     */
    public function balance(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $wallet = $user->wallet;

        return [
            'balance' => $wallet->spendableBalance()
        ];
    }

    public function deposit(array $data): array
    {
//        $amount = $data['amount'];
//        $network = $data['network'];

        // TODO: Generate payment link and send user
        $url = '';

        // TODO: generate a pending transaction, before sending back the URL

        // TODO: Dispatch a job to update the balance and send email to user, when the transaction was finalized

        return [
            'url' => $url,
        ];
    }

    public function withdraw(array $data): array
    {
        $amount = $data['amount'];
        $network = $data['network'];
        $walletAddress = $data['walletAddress'];

        // TODO: Check user's wallet
        // TODO: Send request to OxaPay API for withdraw
        // TODO: Create a pending transaction
        // TODO: Return transaction, tx_hash, ...
        // TODO: Dispatch a job to update balance and send email to user when the transaction was finalized

        return [
            'transaction' => null
        ];
    }
}