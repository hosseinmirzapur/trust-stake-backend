<?php

namespace App\Services;

use App\External\PaymentService;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class WalletService
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * @return array
     */
    public function balance(): array
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Wallet $usdtWallet */
        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();

        return [
            'balance' => !is_null($usdtWallet) ? $usdtWallet->spendableBalance() : 0
        ];
    }

    public function deposit(array $data): array
    {
        $amount = $data['amount'];
        $network = $data['network'];

        /** @var User $user */
        $user = auth()->user();

        /** @var Wallet $usdtWallet */
        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();
        if (!$usdtWallet) {
            $usdtWallet = $user->wallets()->create([
                'currency' => 'USDT',
                'balance' => 0
            ]);
        }

        // Generate callback and return URLs
        $callbackUrl = URL::to('/api/payment/callback');
        // Return to payment gateway instead of success page
        $returnUrl = 'https://app.oxapay.com/payment/status'; // Redirect back to OxaPay

        // Generate invoice using OxaPay
        $invoiceResult = $this->paymentService->generateInvoice(
            $amount,
            $network,
            $callbackUrl,
            $returnUrl,
            $user->email,
            'DEP-' . time() . '-' . $user->id
        );

        if (!$invoiceResult['success']) {
            Log::error('Failed to generate invoice for user ' . $user->id . ': ' . $invoiceResult['error']);
            return [
                'success' => false,
                'error' => $invoiceResult['error'],
            ];
        }

        // Create pending transaction
        $transaction = Transaction::query()->create([
            'wallet_id' => $usdtWallet->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'status' => Transaction::STATUS_PENDING,
            'balance_before' => $usdtWallet->balance,
            'balance_after' => $usdtWallet->balance,
            'tx_hash' => $invoiceResult['track_id'] ?? null,
        ]);

        return [
            'success' => true,
            'payment_url' => $invoiceResult['payment_url'],
            'track_id' => $invoiceResult['track_id'],
            'transaction_id' => $transaction->id,
            'expires_at' => $invoiceResult['expired_at'],
        ];
    }

    public function withdraw(array $data): array
    {
        $amount = $data['amount'];
        $network = $data['network'];
        $walletAddress = $data['walletAddress'];

        /** @var User $user */
        $user = auth()->user();
        /** @var Wallet $usdtWallet */
        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();

        // Check if user has sufficient balance
        if ($usdtWallet->spendableBalance() < $amount) {
            return [
                'success' => false,
                'error' => 'Insufficient balance',
            ];
        }

        // Validate wallet address format based on network
        if (!$this->validateWalletAddress($walletAddress, $network)) {
            return [
                'success' => false,
                'error' => 'Invalid wallet address for the selected network',
            ];
        }

        // Generate callback URL
        $callbackUrl = URL::to('/api/payment/withdrawal/callback');

        // Process withdrawal using OxaPay
        $withdrawalResult = $this->paymentService->processWithdrawal(
            $amount,
            $walletAddress,
            $network,
            $callbackUrl
        );

        if (!$withdrawalResult['success']) {
            Log::error('Failed to process withdrawal for user ' . $user->id . ': ' . $withdrawalResult['error']);
            return [
                'success' => false,
                'error' => $withdrawalResult['error'],
            ];
        }

        // Create pending transaction
        $transaction = Transaction::create([
            'wallet_id' => $usdtWallet->id,
            'type' => Transaction::TYPE_WITHDRAW,
            'amount' => $amount,
            'status' => Transaction::STATUS_PENDING,
            'balance_before' => $usdtWallet->balance,
            'balance_after' => $usdtWallet->balance - $amount,
            'tx_hash' => $withdrawalResult['tx_hash'] ?? null,
        ]);

        // Update wallet balance immediately for withdrawal (pending until confirmed)
        $usdtWallet->decrement('balance', $amount);

        return [
            'success' => true,
            'transaction_id' => $transaction->id,
            'tx_hash' => $withdrawalResult['tx_hash'],
            'status' => $withdrawalResult['status'],
        ];
    }

    /**
     * Validate wallet address based on network
     *
     * @param string $address
     * @param string $network
     * @return bool
     */
    private function validateWalletAddress(string $address, string $network): bool
    {
        // Basic validation - in production, you would use network-specific validation
        $network = strtolower($network);

        // Common crypto address patterns
        $patterns = [
            'ethereum' => '/^0x[a-fA-F0-9]{40}$/',
            'tron' => '/^T[a-zA-Z0-9]{33}$/',
            'bitcoin' => '/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$/',
            'binance' => '/^0x[a-fA-F0-9]{40}$/', // BSC uses same format as Ethereum
        ];

        if (isset($patterns[$network])) {
            return preg_match($patterns[$network], $address) === 1;
        }

        // For unknown networks, perform basic length check
        return strlen($address) >= 26 && strlen($address) <= 42;
    }
}
