<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

class SubscriptionService
{
    public function buy(array $data, int $plan_id): array
    {
        $plan = Plan::query()->find($plan_id);
        abort_if(!$plan, 404, 'Plan not found');

        abort_if($plan->disabled, 400, "Plan is disabled, can't purchase subscription.");

        $paymentType = $data['paymentType'];

        if ($paymentType === Transaction::PAYMENT_GATEWAY) {
            // generate payment gateway url and return to user
            return $this->paymentGatewayProcess();
        }

        return $this->walletProcess($plan);
    }

    private function paymentGatewayProcess(): array
    {

        return [];
    }

    /**
     * @param Plan $plan
     * @return array
     */
    private function walletProcess(Plan $plan): array
    {
        /** @var User $user */
        $user = auth()->user();

        $wallet = $user->wallet;
        abort_if(!$wallet, 400, 'Wallet has not been initialized yet.');

        if ($wallet->spendableBalance() < $plan->price) {
            abort(422, 'Balance not enough');
        }

        [$sub, $tx] = DB::transaction(function () use ($plan, $user, $wallet) {
            $sub = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addDays($plan->lock_time_in_days),
            ]);

            $tx = $wallet->transactions()->create([
                'type' => Transaction::TYPE_PAYMENT,
                'amount' => $plan->price,
                'status' => Transaction::STATUS_COMPLETED,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance - $plan->price,
                'tx_hash' => Transaction::generateManualHash()
            ]);

            $wallet->update([
                'balance' => $wallet->balance - $plan->price,
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