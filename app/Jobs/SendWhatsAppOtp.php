<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppOtp implements ShouldQueue
{
    use Queueable;

    public $timeout = 30; // 30 seconds timeout for the job
    public $tries = 3;    // Retry up to 3 times if it fails

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $mobile,
        private readonly string $otpCode,
        private readonly ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting WhatsApp OTP job', [
                'mobile' => $this->mobile,
                'otp_length' => strlen($this->otpCode),
                'user_id' => $this->userId,
                'job_id' => $this->job->getJobId()
            ]);

            $whatsappService = new WhatsAppService();

            // First, try to initialize the session
            $initResult = $whatsappService->initializeSession();
            if (!$initResult['success']) {
                Log::warning('WhatsApp session initialization failed, proceeding with send attempt', [
                    'mobile' => $this->mobile,
                    'user_id' => $this->userId,
                    'init_result' => $initResult,
                    'job_id' => $this->job->getJobId()
                ]);
            }

            $result = $whatsappService->sendOtp($this->mobile, $this->otpCode);

            if (isset($result['success']) && $result['success']) {
                Log::info('WhatsApp OTP job completed successfully', [
                    'mobile' => $this->mobile,
                    'user_id' => $this->userId,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                $error = $result['error'] ?? 'Unknown error';

                // Check if retry is possible for certain errors
                if (isset($result['retry_possible']) && $result['retry_possible']) {
                    Log::warning('WhatsApp OTP job failed but retry possible', [
                        'mobile' => $this->mobile,
                        'user_id' => $this->userId,
                        'error' => $error,
                        'job_id' => $this->job->getJobId()
                    ]);

                    // Try once more after a short delay
                    sleep(2);
                    $retryResult = $whatsappService->sendOtp($this->mobile, $this->otpCode);

                    if ($retryResult['success']) {
                        Log::info('WhatsApp OTP retry succeeded', [
                            'mobile' => $this->mobile,
                            'user_id' => $this->userId,
                            'job_id' => $this->job->getJobId()
                        ]);
                        return;
                    } else {
                        $error = $retryResult['error'] ?? $error;
                    }
                }

                Log::error('WhatsApp OTP job failed', [
                    'mobile' => $this->mobile,
                    'user_id' => $this->userId,
                    'error' => $error,
                    'result' => $result,
                    'job_id' => $this->job->getJobId()
                ]);

                // If WhatsApp fails and we have user ID, try email fallback
                if ($this->userId) {
                    $this->tryEmailFallback();
                }

                // Don't throw exception - let the job complete even if WhatsApp fails
                // The email fallback will handle delivery if needed
            }
        } catch (Exception $e) {
            Log::error('Exception in WhatsApp OTP job', [
                'mobile' => $this->mobile,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job->getJobId()
            ]);

            // If we have user ID, try email fallback even on exception
            if ($this->userId) {
                $this->tryEmailFallback();
            }

            // For critical exceptions, we might want to retry
            // For now, don't throw to prevent infinite retries
            Log::warning('WhatsApp OTP job completed with exception - not retrying', [
                'mobile' => $this->mobile,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId()
            ]);
        }
    }

    /**
     * Try email fallback if WhatsApp fails
     */
    private function tryEmailFallback(): void
    {
        try {
            $user = \App\Models\User::find($this->userId);
            if ($user && $user->email) {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\OtpMail($this->otpCode, $user->email));

                Log::info('WhatsApp OTP job - Email fallback sent successfully', [
                    'mobile' => $this->mobile,
                    'email' => $user->email,
                    'user_id' => $this->userId,
                    'job_id' => $this->job->getJobId()
                ]);
            }
        } catch (Exception $e) {
            Log::error('WhatsApp OTP job - Email fallback also failed', [
                'mobile' => $this->mobile,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId()
            ]);
        }
    }
}
