<?php

namespace App\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private string $baseUrl;
    private string $apiKey;
    private bool $sandbox;

    public function __construct()
    {
        $this->baseUrl = config('services.oxapay.base_url', 'https://api.oxapay.com/v1');
        $this->apiKey = config('services.oxapay.api_key');
        $this->sandbox = config('services.oxapay.sandbox', false);
    }

    /**
     * Generate an invoice for deposit
     *
     * @param float $amount
     * @param string $network
     * @param string $callbackUrl
     * @param string $returnUrl
     * @param string $email
     * @param string $orderId
     * @return array
     */
    public function generateInvoice(
        float $amount,
        string $network,
        string $callbackUrl,
        string $returnUrl,
        string $email = '',
        string $orderId = ''
    ): array {
        $payload = [
            'amount' => $amount,
            'currency' => 'USDT',
            'to_currency' => 'USDT',
            'callback_url' => $callbackUrl,
            'return_url' => $returnUrl,
            'sandbox' => $this->sandbox,
            'lifetime' => 60, // 60 minutes expiration
        ];

        // Add optional parameters if provided
        if (!empty($email)) {
            $payload['email'] = $email;
        }

        if (!empty($orderId)) {
            $payload['order_id'] = $orderId;
        }

        // Add network-specific parameters
        $payload['description'] = "Deposit via {$network} network";

        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payment/invoice", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'payment_url' => $data['data']['payment_url'] ?? '',
                    'track_id' => $data['data']['track_id'] ?? '',
                    'expired_at' => $data['data']['expired_at'] ?? null,
                    'date' => $data['data']['date'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to generate invoice',
            ];

        } catch (\Exception $e) {
            Log::error('OxaPay invoice generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Payment service temporarily unavailable',
            ];
        }
    }

    /**
     * Process withdrawal request
     *
     * @param float $amount
     * @param string $walletAddress
     * @param string $network
     * @param string $callbackUrl
     * @return array
     */
    public function processWithdrawal(
        float $amount,
        string $walletAddress,
        string $network,
        string $callbackUrl
    ): array {
        $payload = [
            'amount' => $amount,
            'address' => $walletAddress,
            'currency' => 'USDT',
            'callback_url' => $callbackUrl,
            'sandbox' => $this->sandbox,
        ];

        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/merchant/withdraw", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'transaction_id' => $data['data']['transaction_id'] ?? '',
                    'tx_hash' => $data['data']['tx_hash'] ?? '',
                    'status' => $data['data']['status'] ?? '',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to process withdrawal',
            ];

        } catch (\Exception $e) {
            Log::error('OxaPay withdrawal failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Withdrawal service temporarily unavailable',
            ];
        }
    }

    /**
     * Verify payment status
     *
     * @param string $trackId
     * @return array
     */
    public function verifyPayment(string $trackId): array
    {
        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payment/verify", [
                'track_id' => $trackId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'status' => $data['data']['status'] ?? '',
                    'paid_amount' => $data['data']['paid_amount'] ?? 0,
                    'paid_currency' => $data['data']['paid_currency'] ?? '',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to verify payment',
            ];

        } catch (\Exception $e) {
            Log::error('OxaPay payment verification failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Payment verification service temporarily unavailable',
            ];
        }
    }

    /**
     * Handle payment callback from OxaPay
     *
     * @param array $callbackData
     * @return array
     */
    public function handlePaymentCallback(array $callbackData): array
    {
        // Validate callback data
        $requiredFields = ['track_id', 'status', 'amount', 'currency'];
        foreach ($requiredFields as $field) {
            if (!isset($callbackData[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }

        $trackId = $callbackData['track_id'];
        $status = $callbackData['status'];
        $amount = $callbackData['amount'];
        $currency = $callbackData['currency'];

        // Verify the payment status with OxaPay
        $verification = $this->verifyPayment($trackId);
        
        if (!$verification['success']) {
            return $verification;
        }

        // Check if the verified status matches the callback status
        if ($verification['status'] !== $status) {
            return ['success' => false, 'error' => 'Status mismatch between callback and verification'];
        }

        return [
            'success' => true,
            'track_id' => $trackId,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
            'verified_data' => $verification,
        ];
    }
}
