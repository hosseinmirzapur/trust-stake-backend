<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\External\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Random\RandomException;

class SubscriptionService
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function buy(array $data, int $plan_id): array
    {
        $plan = Plan::query()->find($plan_id);
        abort_if(!$plan, 404, 'Plan not found');

        abort_if($plan->disabled, 400, "Plan is disabled, can't purchase subscription.");

        $paymentType = $data['paymentType'];

        if ($paymentType === Transaction::PAYMENT_GATEWAY) {
            // generate payment gateway url and return to user
            return $this->paymentGatewayProcess($plan);
        }

        return $this->walletProcess($plan);
    }

    private function paymentGatewayProcess(Plan $plan): array
    {
        /** @var User $user */
        $user = auth()->user();

        // Generate callback and return URLs for subscription purchase
        $callbackUrl = URL::to('/api/payment/callback');
        // Return to payment gateway instead of success page
        $returnUrl = 'https://app.oxapay.com/payment/status'; // Redirect back to OxaPay

        // Generate invoice using OxaPay for subscription purchase
        $invoiceResult = $this->paymentService->generateInvoice(
            $plan->price,
            'TRON', // Default network for subscriptions
            $callbackUrl,
            $returnUrl,
            $user->email,
            'SUB-' . time() . '-' . $user->id
        );

        if (!$invoiceResult['success']) {
            Log::error('Failed to generate subscription payment invoice for user ' . $user->id . ': ' . $invoiceResult['error']);
            return [
                'success' => false,
                'error' => $invoiceResult['error'],
            ];
        }

        return [
            'success' => true,
            'payment_url' => $invoiceResult['payment_url'],
            'track_id' => $invoiceResult['track_id'],
            'expires_at' => $invoiceResult['expired_at'],
        ];
    }

    /**
     * @param Plan $plan
     * @return array
     */
    private function walletProcess(Plan $plan): array
    {
        /** @var User $user */
        $user = auth()->user();

        abort_if(!$user->wallets()->count() == 0, 400, 'No wallet has been initialized yet.');

        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();

        if ($usdtWallet->spendableBalance() < $plan->price) {
            abort(422, 'Balance not enough');
        }

        [$sub, $tx] = DB::transaction(function () use ($plan, $user, $usdtWallet) {
            $sub = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addDays($plan->lock_time_in_days),
            ]);

            $tx = $usdtWallet->transactions()->create([
                'type' => Transaction::TYPE_PAYMENT,
                'amount' => $plan->price,
                'status' => Transaction::STATUS_COMPLETED,
                'balance_before' => $usdtWallet->balance,
                'balance_after' => $usdtWallet->balance - $plan->price,
                'tx_hash' => Transaction::generateManualHash()
            ]);

            $usdtWallet->update([
                'balance' => $usdtWallet->balance - $plan->price,
            ]);

            return [$sub, $tx];
        });
        return [
            'sub' => $sub,
            'tx' => $tx,
        ];
    }

    public function my(): array
    {
        /** @var User $user */
        $user= auth()->user();

        $subs = $user->subscriptions()->with('plan')->get();
        return [
            'subs' => $subs,
        ];
    }
}
