<?php

namespace App\Services;

use App\Jobs\SendWhatsAppOtp;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\WhatsAppNumber;
use App\Services\WhatsAppService;
use Exception;
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
        if (isset($data['email'])) {
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

        // Generate OTP code first
        $otpCode = random_int(100000, 999999);

        // Cache the OTP code for verification
        Cache::put("sign-in-token-$user->id", $otpCode, self::REMEMBER_TTL);

        // Try WhatsApp using available connected numbers
        $whatsappResult = $this->sendOtpToWhatsapp($user->mobile, $otpCode);

        if ($whatsappResult['success']) {
            Log::info('WhatsApp OTP sent for mobile login', [
                'mobile' => $mobile,
                'user_id' => $user->id,
                'otp_length' => strlen($otpCode)
            ]);

            $whatsappSent = true;
            $fallbackMethod = 'whatsapp_sync';
        } else {
            Log::error('Failed to send WhatsApp OTP for login', [
                'mobile' => $mobile,
                'user_id' => $user->id,
                'error' => $whatsappResult['error'] ?? 'Unknown error'
            ]);

            $whatsappSent = false;
        }

        // Always send email as well for guaranteed delivery
        if ($user->email) {
            try {
                $this->sendEmailWithOtp($user->email, $otpCode);
                $emailFallback = 'sent';

                if (!$whatsappSent) {
                    $fallbackMethod = 'email_primary';
                } else {
                    $fallbackMethod = 'whatsapp_and_email';
                }

                Log::info('Email OTP sent as fallback/guarantee', [
                    'mobile' => $mobile,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'whatsapp_also_sent' => $whatsappSent
                ]);
            } catch (Exception $e) {
                Log::error('Email OTP fallback also failed', [
                    'mobile' => $mobile,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                if (!$whatsappSent) {
                    $fallbackMethod = 'both_failed';
                } else {
                    $fallbackMethod = 'whatsapp_only';
                }

                $emailFallback = 'failed';
            }
        } else {
            if (!$whatsappSent) {
                $fallbackMethod = 'whatsapp_failed_no_email';
            }
            $emailFallback = 'no_email';
        }

        // In production the code should not be returned
        return [
            'code' => $otpCode,
            'whatsapp_sent' => $whatsappSent,
            'email_sent' => $emailFallback === 'sent',
            'fallback_method' => $fallbackMethod,
        ];
    }

    /**
     * Send OTP via email using Mailtrap
     */
    private function sendEmailWithOtp(string $email, string $otpCode): void
    {
        try {
            Mail::to($email)->send(new OtpMail($otpCode, $email));
            Log::info("Sent $otpCode to email: $email");
        } catch (Exception $e) {
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
            // Get an available connected WhatsApp number for sending OTP
            $whatsappNumber = WhatsAppNumber::getLeastUsedAvailableNumber();

            if (!$whatsappNumber) {
                Log::warning('No available connected WhatsApp numbers for OTP sending', [
                    'mobile' => $mobile,
                    'otp_length' => strlen($otpCode)
                ]);

                return [
                    'success' => false,
                    'error' => 'No available WhatsApp numbers configured',
                    'retry_possible' => false
                ];
            }

            // Check if this specific number's session is actually connected
            $whatsappService = new WhatsAppService($whatsappNumber->session_id);
            $status = $whatsappService->getSessionStatus();

            if (!$status['success'] || !$status['connected']) {
                Log::warning('WhatsApp number session not connected', [
                    'mobile' => $mobile,
                    'whatsapp_number' => $whatsappNumber->mobile,
                    'session_id' => $whatsappNumber->session_id,
                    'status' => $status
                ]);

                // Mark as error since it's supposed to be connected
                $whatsappNumber->markError();

                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected',
                    'retry_possible' => true,
                    'status' => $status
                ];
            }

            // Send OTP via WhatsApp using the selected number's session
            $result = $whatsappService->sendOtpWithSession($mobile, $otpCode, $whatsappNumber->session_id);

            if (isset($result['success']) && $result['success']) {
                // Mark the number as successfully used
                $whatsappNumber->markAsUsed();

                Log::info('WhatsApp OTP sent successfully', [
                    'mobile' => $mobile,
                    'whatsapp_number' => $whatsappNumber->mobile,
                    'session_id' => $whatsappNumber->session_id,
                    'otp_length' => strlen($otpCode)
                ]);
            } else {
                // Mark the number with error
                $whatsappNumber->markError();

                Log::error('WhatsApp OTP sending failed', [
                    'mobile' => $mobile,
                    'whatsapp_number' => $whatsappNumber->mobile,
                    'session_id' => $whatsappNumber->session_id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Exception while sending WhatsApp OTP', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while sending OTP',
                'details' => $e->getMessage(),
                'retry_possible' => true
            ];
        }
    }

    public function signup(array $data): array
    {
        if (isset($data['email'])) {
            return $this->handleEmailSignup($data['email']);
        }
        return $this->handleMobileSignup($data['mobile']);
    }

    public function verifyOtp(array $data): array
    {
        $otp = $data['otp'];
        $type = $data['type'];
        $credential = $data['credential'];
        $user = null;

        // Determine if credential is email or mobile
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            /** @var User $user */
            $user = User::query()->where('email', $credential)->first();
        } else {
            // Treat as mobile number
            /** @var User $user */
            $user = User::query()->where('mobile', $credential)->first();
        }

        if (!$user) {
            abort(404, 'No user found with these credentials');
        }

        if ($type === 'login') {
            return $this->verifyLoginOtp($user, $otp);
        } else {
            return $this->verifySignupOtp($user, $otp);
        }
    }

    private function verifyLoginOtp(User $user, string $otp): array
    {
        $cachedOtp = Cache::get("sign-in-token-$user->id");

        if (!$cachedOtp || $cachedOtp != $otp) {
            abort(401, 'Invalid OTP code');
        }

        // Clear the OTP from cache
        Cache::forget("sign-in-token-$user->id");

        // Create Sanctum token for authenticated user
        $token = $user->createToken('authToken')->plainTextToken;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
            ],
            'token' => $token,
            'two_factor_enabled' => !empty($user->google2fa_secret),
        ];
    }

    private function verifySignupOtp(User $user, string $otp): array
    {
        $cachedOtp = Cache::get("sign-up-token-$user->id");

        if (!$cachedOtp || $cachedOtp != $otp) {
            abort(401, 'Invalid OTP code');
        }

        // Clear the OTP from cache
        Cache::forget("sign-up-token-$user->id");

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
            ],
            'stepToken' => Cache::get("user-step-token-$user->id"),
        ];
    }

    public function resendOtp(array $data): array
    {
        if (isset($data['email'])) {
            return $this->resendEmailOtp($data['email']);
        }
        return $this->resendMobileOtp($data['mobile']);
    }

    private function resendEmailOtp(string $email): array
    {
        $user = User::query()->where('email', $email)->first();
        abort_if(!$user, 404, 'No user with these credentials were found.');

        $otpCode = random_int(100000, 999999);
        Cache::put("sign-in-token-$user->id", $otpCode, self::REMEMBER_TTL);

        $this->sendEmailWithOtp($user->email, $otpCode);

        return [
            'message' => 'OTP code sent to your email',
            'code' => $otpCode, // Remove in production
        ];
    }

    private function resendMobileOtp(string $mobile): array
    {
        $user = User::query()->where('mobile', $mobile)->first();
        abort_if(!$user, 404, 'No user with these credentials were found.');

        $otpCode = random_int(100000, 999999);

        // Use the same logic as regular OTP sending
        $whatsappResult = $this->sendOtpToWhatsapp($user->mobile, $otpCode);

        if (!isset($whatsappResult['success']) || !$whatsappResult['success']) {
            Log::warning('WhatsApp OTP failed during resend', [
                'mobile' => $mobile,
                'error' => $whatsappResult['error'] ?? 'Unknown error'
            ]);
        }

        Cache::put("sign-in-token-$user->id", $otpCode, self::REMEMBER_TTL);

        return [
            'message' => 'OTP code sent via WhatsApp',
            'code' => $otpCode, // Remove in production
            'whatsapp_sent' => $whatsappResult['success'] ?? false,
        ];
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

        // Generate OTP for verification
        $otpCode = random_int(100000, 999999);

        // Try WhatsApp using available connected numbers
        $whatsappResult = $this->sendOtpToWhatsapp($user->mobile, $otpCode);

        if ($whatsappResult['success']) {
            Log::info('WhatsApp OTP sent for mobile signup', [
                'mobile' => $mobile,
                'user_id' => $user->id,
                'otp_length' => strlen($otpCode)
            ]);

            $whatsappSent = true;
            $fallbackMethod = 'whatsapp_sync';
        } else {
            Log::error('Failed to send WhatsApp OTP for signup', [
                'mobile' => $mobile,
                'user_id' => $user->id,
                'error' => $whatsappResult['error'] ?? 'Unknown error'
            ]);

            $whatsappSent = false;
        }

        // Always send email for guaranteed delivery during signup
        if ($user->email) {
            try {
                $this->sendEmailWithOtp($user->email, $otpCode);
                $emailFallback = 'sent';

                if (!$whatsappSent) {
                    $fallbackMethod = 'email_primary_signup';
                } else {
                    $fallbackMethod = 'whatsapp_and_email_signup';
                }

                Log::info('Email OTP sent for signup guarantee', [
                    'mobile' => $mobile,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'whatsapp_also_sent' => $whatsappSent
                ]);
            } catch (Exception $e) {
                Log::error('Email OTP for signup also failed', [
                    'mobile' => $mobile,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                if (!$whatsappSent) {
                    $fallbackMethod = 'both_failed_signup';
                } else {
                    $fallbackMethod = 'whatsapp_only_signup';
                }

                $emailFallback = 'failed';
            }
        } else {
            if (!$whatsappSent) {
                $fallbackMethod = 'whatsapp_failed_no_email_signup';
            }
            $emailFallback = 'no_email';
        }

        $authToken = $user->createToken('authToken')->plainTextToken;
        $stepToken = Str::uuid();

        Cache::put("user-step-token-$user->id", $stepToken, self::REMEMBER_TTL);
        Cache::put("sign-up-token-$user->id", $otpCode, self::REMEMBER_TTL);

        Cache::put("sign-up-token-$user->id", $otpCode, self::REMEMBER_TTL);

        return [
            'authToken' => $authToken,
            'stepToken' => $stepToken,
            'code' => $otpCode, // Remove in production
            'whatsapp_sent' => $whatsappSent,
            'email_sent' => $emailFallback === 'sent',
            'fallback_method' => $fallbackMethod,
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

        // Send OTP for verification
        $otpCode = random_int(100000, 999999);
        $this->sendEmailWithOtp($user->email, $otpCode);

        $authToken = $user->createToken('authToken')->plainTextToken;
        $stepToken = Str::uuid();

        Cache::put("user-step-token-$user->id", $stepToken, self::REMEMBER_TTL);
        Cache::put("sign-up-token-$user->id", $otpCode, self::REMEMBER_TTL);

        return [
            'authToken' => $authToken,
            'stepToken' => $stepToken,
            'code' => $otpCode, // Remove in production
        ];
    }

    public function registerDetails(array $data): array
    {
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            abort(401, 'Credential is required');
        }

        // Find user by credential (email or mobile)
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()->where('email', $credential)->first();
        } else {
            $user = User::query()->where('mobile', $credential)->first();
        }

        if (!$user) {
            abort(404, 'User not found');
        }

        $previousToken = Cache::get("user-step-token-$user->id");

        if (!$previousToken || $previousToken != $data['token']) {
            abort(401, 'invalid signup token');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'country' => $data['country'],
            'mobile' => $data['mobile'] ?? $user->mobile,
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
