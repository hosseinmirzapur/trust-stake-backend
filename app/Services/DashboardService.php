<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Random\RandomException;

class DashboardService
{
    private const REMEMBER_TTL = 300; // seconds

    public function index(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();
        $walletBalance = !is_null($usdtWallet) ? $usdtWallet->spendableBalance() : 0;

        $subStats = $user->subscriptions();
        $active_count = $subStats->where('status', Subscription::STATUS_ACTIVE)->count();
        $inactive_count = $subStats->where('status', Subscription::STATUS_ACTIVE)->count();

        $tickets = $user->tickets()->count();
        $referrals = $user->referrals()->count();

        $subs = $user->subscriptions()
            ->with('plan')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->get();

        $notifications = $user->notifications;

        $plans = Plan::query()->where('disabled', false)->get();

        return compact(
            'walletBalance',
            'active_count',
            'inactive_count',
            'tickets',
            'referrals',
            'subs',
            'notifications',
            'plans'
        );
    }

    public function profile(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'user' => $user
        ];
    }

    public function wallet(): array
    {
        /** @var User $user */
        $user = auth()->user();
        if ($user->wallets()->count() === 0) {
            $user->wallets()->create([
                'balance' => 0,
                'currency' => 'USDT'
            ]);
        }

        /** @var Wallet $usdtWallet */
        $usdtWallet = $user->wallets()->where('currency', 'USDT')->first();

        $balance = $usdtWallet->spendableBalance();

        $transactions = $usdtWallet->transactions()->get();
        $tableData = [];

        foreach ($transactions as $tx) {
            $tableData[] = [
                'id' => $tx->tx_hash,
                'status' => $tx->status,
                'network' => $tx->network,
                'amount' => $tx->amount,
                'created_at' => $tx->created_at,
                'balance_before' => $tx->balance_before,
                'balance_after' => $tx->balance_after,
                'address' => $tx->wallet->address,
            ];
        }

        return compact('balance', 'tableData');
    }

    public function modifyProfile(array $data): array
    {
        if (isset($data['profile_image'])) {
            $path = 'profile_images/';
            /** @var UploadedFile $file */
            $file = $data['profile_image'];
            $fileName = $this->generateFileName($file);
            Storage::putFileAs($path, $file, $fileName);

            $data['profile_image'] = $path . '/' . $fileName;
        }

        /** @var User $user */
        $user = auth()->user();
        if (isset($data['email'])) {
            if (User::query()->where('email', $data['email'])->whereNot('id', $user->id)->exists()) {
                abort(400, 'This email address has already been taken.');
            }
            $data['email_verified_at'] = null;
        }

        if (isset($data['mobile'])) {
            if (User::query()->where('mobile', $data['mobile'])->whereNot('id', $user->id)->exists()) {
                abort(400, 'This phone number has already been taken.');
            }
            $data['mobile_verified_at'] = null;
        }
        $user->update($data);


        return compact('user');
    }

    /**
     * @throws RandomException
     */
    public function sendEmailVerificationCode(): array
    {
        /** @var User $user */
        $user = auth()->user();
        abort_if($user->hasVerifiedEmail(), 400, 'User has already been verified.');

        $code = 'truststake-' . random_int(100000, 999999);
        Cache::put("verify-email-$user->id", $code, self::REMEMBER_TTL);
        try {
            Mail::to($user->email)->send(new OtpMail($code, $user->email));
        } catch (Exception $e) {
            // Log the error but don't fail the request
            Log::error('Failed to send OTP email: ' . $e->getMessage());
        }


        return [
            'message' => 'OTP sent successfully.',
        ];
    }

    public function verifyEmail(array $data): array
    {
        /** @var User $user */
        $user = auth()->user();
        abort_if($user->hasVerifiedEmail(), 400, 'User has already been verified.');

        $sentCode = Cache::get("verify-email-$user->id");
        if ($sentCode != $data['code']) {
            abort(400, 'Invalid code.');
        }

        return [
            'result' => $user->markEmailAsVerified(),
        ];
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     * @throws Exception
     */
    public function activate2FA(): array
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user->hasVerifiedEmail()) {
            abort(400, 'User has not verified email.');
        }

        if ($user->hasTwoFactor) {
            abort(400, 'Two-factor authentication already verified.');
        }

        $twoFA = $user->generate2FASecret();

        if ($user->twoFactorKey !== $twoFA['secret']) {
            throw new Exception('Generating 2FA Secret failed.');
        }


        return [
            'secret' => $twoFA['secret'],
            'qrUrl' => $twoFA['qrUrl'],
        ];
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function verify2FA(array $data): array
    {
        $code = $data['authenticator_code'];

        /** @var User $user */
        $user = auth()->user();
        if ($user->hasTwoFactor) {
            abort(400, 'Two-factor authentication already verified.');
        }

        // check sent code
        $google2fa = new Google2FA();
        $verified = $google2fa->verifyKey($user->twoFactorKey, $code);

        if (!$verified) {
            abort(400, 'Verification failed. Enter the new code once it shows up in your authenticator app');
        }

        $user->update([
            'hasTwoFactor' => true
        ]);

        return compact('verified');
    }

    public function referral(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $referralCode = $user->referral_code;

        /** @var int[] $referredUsers */
        $referredUsers = Referral::query()->where('referred_by', $user->id)->pluck('referred_id');

        $data = [];
        foreach ($referredUsers as $userId) {
            $sub = Subscription::with('plan', fn($q) => $q->select(['price', 'profit']))
                ->where('user_id', $userId)
                ->where('active', true)
                ->select(['start_date', 'end_date'])
                ->latest()
                ->first();
            if ($sub) {
                $totalProfit = $sub->plan->price + ($sub->plan->profit * $sub->plan->price);
                $profitInOneDay = $totalProfit / $sub->plan->lock_time_in_days;
                $daysUntilToday = Carbon::parse($sub->start_date)->diffInDays(Carbon::now());
                $calculatedProfit = $profitInOneDay * $daysUntilToday;

                $data[] = [
                    'user_id' => $userId,
                    'start_date' => $sub->start_date,
                    'end_date' => $sub->end_date,
                    'profit' => $calculatedProfit,
                    'status' => 'success'
                ];
            }
        }
        return compact('referralCode', 'data');
    }

    private function generateFileName(UploadedFile $file): string
    {
        return Str::random(20) . '.' . $file->getClientOriginalExtension();
    }
}
