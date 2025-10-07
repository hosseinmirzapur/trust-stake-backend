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
            $result = $whatsappService->sendOtp($this->mobile, $this->otpCode);

            if (isset($result['success']) && $result['success']) {
                Log::info('WhatsApp OTP job completed successfully', [
                    'mobile' => $this->mobile,
                    'user_id' => $this->userId,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::error('WhatsApp OTP job failed', [
                    'mobile' => $this->mobile,
                    'user_id' => $this->userId,
                    'error' => $result['error'] ?? 'Unknown error',
                    'job_id' => $this->job->getJobId()
                ]);

                // If WhatsApp fails and we have user ID, try email fallback
                if ($this->userId) {
                    $this->tryEmailFallback();
                }
            }
        } catch (Exception $e) {
            Log::error('Exception in WhatsApp OTP job', [
                'mobile' => $this->mobile,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job->getJobId()
            ]);

            // If we have user ID, try email fallback
            if ($this->userId) {
                $this->tryEmailFallback();
            }

            throw $e; // Re-throw to mark job as failed
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
