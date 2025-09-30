<?php

namespace App\Http\Controllers;

use App\External\PaymentService;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * Handle payment callback from OxaPay
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handlePaymentCallback(Request $request): JsonResponse
    {
        Log::info('Payment callback received:', $request->all());

        $callbackData = $request->all();
        $result = $this->paymentService->handlePaymentCallback($callbackData);

        if (!$result['success']) {
            Log::error('Payment callback validation failed:', $result);
            return response()->json($result, 400);
        }

        $trackId = $result['track_id'];
        $status = $result['status'];
        $amount = $result['amount'];

        // Find transaction by track_id (tx_hash)
        $transaction = Transaction::where('tx_hash', $trackId)->first();

        if (!$transaction) {
            Log::error('Transaction not found for track_id: ' . $trackId);
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], 404);
        }

        $wallet = $transaction->wallet;

        // Handle different payment statuses
        switch ($status) {
            case 'paid':
                // Update transaction status
                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED,
                    'balance_after' => $wallet->balance + $amount
                ]);

                // Update wallet balance
                $wallet->increment('balance', $amount);

                Log::info('Payment completed successfully', [
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount
                ]);
                break;

            case 'expired':
            case 'failed':
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED
                ]);
                Log::info('Payment failed or expired', [
                    'transaction_id' => $transaction->id,
                    'status' => $status
                ]);
                break;

            default:
                Log::warning('Unknown payment status received', [
                    'transaction_id' => $transaction->id,
                    'status' => $status
                ]);
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle withdrawal callback from OxaPay
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWithdrawalCallback(Request $request): JsonResponse
    {
        Log::info('Withdrawal callback received:', $request->all());

        $callbackData = $request->all();
        
        // Validate required fields for withdrawal callback
        $requiredFields = ['transaction_id', 'status', 'tx_hash'];
        foreach ($requiredFields as $field) {
            if (!isset($callbackData[$field])) {
                Log::error('Missing required field in withdrawal callback: ' . $field);
                return response()->json([
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ], 400);
            }
        }

        $txHash = $callbackData['tx_hash'];
        $status = $callbackData['status'];
        $transactionId = $callbackData['transaction_id'];

        // Find transaction by tx_hash
        $transaction = Transaction::where('tx_hash', $txHash)->first();

        if (!$transaction) {
            Log::error('Withdrawal transaction not found for tx_hash: ' . $txHash);
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], 404);
        }

        // Handle different withdrawal statuses
        switch ($status) {
            case 'completed':
                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED
                ]);
                Log::info('Withdrawal completed successfully', [
                    'transaction_id' => $transaction->id,
                    'tx_hash' => $txHash
                ]);
                break;

            case 'failed':
                // Refund the amount back to wallet
                $wallet = $transaction->wallet;
                $wallet->increment('balance', $transaction->amount);
                
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'balance_after' => $wallet->balance
                ]);
                Log::info('Withdrawal failed, amount refunded', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount
                ]);
                break;

            default:
                Log::warning('Unknown withdrawal status received', [
                    'transaction_id' => $transaction->id,
                    'status' => $status
                ]);
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle payment success redirect
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function paymentSuccess(Request $request): JsonResponse
    {
        $trackId = $request->query('track_id');
        
        if (!$trackId) {
            return response()->json([
                'success' => false,
                'error' => 'Missing track_id parameter'
            ], 400);
        }

        // Verify payment status
        $verification = $this->paymentService->verifyPayment($trackId);

        if ($verification['success'] && $verification['status'] === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'paid_amount' => $verification['paid_amount'],
                'paid_currency' => $verification['paid_currency']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Payment not completed or verification failed',
            'details' => $verification
        ], 400);
    }
}
