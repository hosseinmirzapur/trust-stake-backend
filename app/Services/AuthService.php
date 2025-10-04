<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Random\RandomException;

class AuthService
{
    private const REMEMBER_TTL = 300; // seconds

    /**
     * @param array $data
     * @return array
     * @throws RandomException
     */
    public function login(array $data): array
    {
        if ($data['email']) {
            return $this->handleEmailLogin($data['email']);
        }

        return $this->handleMobileLogin($data['mobile']);
    }

    /**
     * @throws RandomException
     */
    private function handleEmailLogin(string $email): array
    {
        $user  = User::query()->where('email', $email)->first();
        abort_if(!$user, 404, 'No user with these credentials were found.');

        // send OTP via Mailtrap
        $otpCode = random_int(100000, 999999);

        Cache::put("sign-in-token-$user->id", $otpCode, self::REMEMBER_TTL);

        $this->sendEmailWithOtp($user->email, $otpCode);

        return [
            'code' => $otpCode,
        ];
    }

    /**
     * @throws RandomException
     */
    private function handleMobileLogin(string $mobile): array
    {
        $user = User::query()->where('mobile', $mobile)->first();
        abort_if(!$user, 404, 'No user with these credentials were found.');

        // Generate and send OTP via WhatsApp
        $otpCode = random_int(100000, 999999);
        $whatsappResult = $this->sendOtpToWhatsapp($user->mobile, $otpCode);

        // If WhatsApp sending fails, we still proceed but log the issue
        // In production, you might want to fallback to email or SMS
        if (!$whatsappResult['success']) {
            Log::warning('WhatsApp OTP failed, but proceeding with login', [
                'mobile' => $mobile,
                'error' => $whatsappResult['error'] ?? 'Unknown error'
            ]);
            // You could implement a fallback mechanism here
            // For now, we'll still cache the OTP and return it
        }

        Cache::put("sign-in-token-$user->id", $otpCode, self::REMEMBER_TTL);

        // In production the code should not be returned
        return [
            'code' => $otpCode,
            'whatsapp_sent' => $whatsappResult['success'] ?? false,
        ];
    }

    /**
     * Send OTP via email using Mailtrap
     */
    private function sendEmailWithOtp(string $email, string $otpCode): void
    {
        try {
            Mail::to($email)->send(new OtpMail($otpCode, $email));
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::error('Failed to send OTP email: ' . $e->getMessage());
        }
    }

    /**
     * Send OTP via WhatsApp API using WhatsApp Plus API
     */
    private function sendOtpToWhatsapp(string $mobile, string $otpCode): array
    {
        try {
            $whatsappService = new WhatsAppService();

            // Initialize session if needed
            $initResult = $whatsappService->initializeSession();
            if (!$initResult['success']) {
                Log::error('WhatsApp session initialization failed', [
                    'mobile' => $mobile,
                    'error' => $initResult
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp service not available'
                ];
            }

            // Send OTP via WhatsApp
            $result = $whatsappService->sendOtp($mobile, $otpCode);

            if ($result['success']) {
                Log::info('WhatsApp OTP sent successfully', [
                    'mobile' => $mobile,
                    'otp_length' => strlen($otpCode)
                ]);
            } else {
                Log::error('WhatsApp OTP sending failed', [
                    'mobile' => $mobile,
                    'error' => $result
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception while sending WhatsApp OTP', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while sending OTP',
                'details' => $e->getMessage()
            ];
        }
    }

    public function signup(array $data): array
    {
        if ($data['email']) {
            return $this->handleEmailSignup($data['email']);
        }
        return $this->handleMobileSignup($data['mobile']);
    }

    private function handleMobileSignup(string $mobile): array
    {
        $user = User::query()->where('mobile', $mobile)->first();
        abort_if($user, 403, 'This user has already been registered.');

        $user = User::query()->create([
            'mobile' => $mobile,
            'referral_code' => User::generateReferralCode(),
            'role' => User::ROLE_USER,
        ]);

        $authToken = $user->createToken('authToken')->plainTextToken;
        $stepToken = Str::uuid();

        Cache::put("user-step-token-$user->id", $stepToken, self::REMEMBER_TTL);

        return [
            'authToken' => $authToken,
            'stepToken' => $stepToken,
        ];
    }

    private function handleEmailSignup(string $email): array {
        $user = User::query()->where('email', $email)->first();
        abort_if($user, 403, 'This user has already been registered.');

        $user = User::query()->create([
            'email' => $email,
            'referral_code' => User::generateReferralCode(),
            'role' => User::ROLE_USER,
        ]);

        $authToken = $user->createToken('authToken')->plainTextToken;
        $stepToken = Str::uuid();

        return [
            'authToken' => $authToken,
            'stepToken' => $stepToken,
        ];
    }

    public function registerDetails(array $data): array
    {
        $user = request()->user();
        $previousToken = Cache::get("user-step-token-$user->id");

        if (!$previousToken || $previousToken != $data['token']) {
            abort(401, 'invalid signup token');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'country' => $data['country'],
            'mobile' => $data['mobile'],
            'email_verified_at' => isset($data['mobile']) ? now() : null,
            'mobile_verified_at' => isset($data['email']) ? now() : null,
        ]);
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
            ],
        ];
    }
}
