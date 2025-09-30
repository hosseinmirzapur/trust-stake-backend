<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-whatsapp-service
                            {--phone= : Phone number to send test OTP (without + or country code)}
                            {--with-otp : Include OTP sending test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp service connectivity and OTP sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing WhatsApp Service...');
        $this->newLine();

        $whatsappService = new WhatsAppService();
        $testPhone = $this->option('phone');
        $withOtp = $this->option('with-otp');

        // Test basic service functionality
        $result = null;
        if ($withOtp && $testPhone) {
            $this->info("Testing with OTP to phone: {$testPhone}");
            $result = $whatsappService->testService($testPhone);
        } else {
            $this->info('Testing service connectivity (no OTP)...');
            $result = $whatsappService->testService();
        }

        // Display results
        if (!$result) {
            $this->error('❌ No test result received');
            $this->error('This might indicate a configuration issue or service unavailability');
            return Command::FAILURE;
        }

        if ($result['success']) {
            $this->info('✅ WhatsApp service test completed successfully!');
        } else {
            $this->error('❌ WhatsApp service test failed!');
            $this->showDebugInfo($result);
        }

        $this->newLine();

        // Show session status
        if (isset($result['session_status'])) {
            $this->info('Session Status:');
            $status = $result['session_status'];
            if ($status['success']) {
                $state = isset($status['state']) ? $status['state'] : 'Unknown';
                $this->line("  State: {$state}");
                $message = isset($status['message']) ? $status['message'] : 'No message';
                $this->line("  Message: {$message}");
            } else {
                $error = isset($status['error']) ? $status['error'] : 'Unknown error';
                $this->line("  Error: {$error}");
            }
        }

        // Show session initialization result
        if (isset($result['session_init'])) {
            $this->newLine();
            $this->info('Session Initialization:');
            $init = $result['session_init'];
            if ($init['success']) {
                $message = isset($init['message']) ? $init['message'] : 'Success';
                $this->line("  ✅ {$message}");
            } else {
                $error = isset($init['error']) ? $init['error'] : 'Unknown error';
                $this->line("  ❌ {$error}");
                if (isset($init['details'])) {
                    $this->line("     Details: {$init['details']}");
                }
            }
        }

        // Show OTP test result
        if (isset($result['otp_test'])) {
            $this->newLine();
            $this->info('OTP Test:');
            $otpTest = $result['otp_test'];
            if ($otpTest['success']) {
                $this->line('  ✅ Test OTP sent successfully');
            } else {
                $error = isset($otpTest['error']) ? $otpTest['error'] : 'Unknown error';
                $this->line("  ❌ {$error}");
                if (isset($otpTest['details'])) {
                    $this->line("     Details: {$otpTest['details']}");
                }
            }
        }

        $this->newLine();
        $this->info('Test completed.');

        $success = isset($result['success']) ? $result['success'] : false;
        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Show debug information for failed tests
     */
    private function showDebugInfo(array $result): void
    {
        $this->newLine();
        $this->warn('=== DEBUG INFORMATION ===');

        // Show current configuration
        $this->info('Current WhatsApp Configuration:');
        $this->line('  API Key: ' . (config('services.whatsapp.api_key') ? '***configured***' : 'NOT SET'));
        $this->line('  API URL: ' . config('services.whatsapp.api_url'));
        $this->line('  Session ID: ' . config('services.whatsapp.session_id'));

        // Show detailed error information
        if (isset($result['error'])) {
            $error = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
            $this->error('Main Error: ' . $error);
        }

        if (isset($result['details'])) {
            $details = is_array($result['details']) ? json_encode($result['details']) : $result['details'];
            $this->warn('Error Details: ' . $details);
        }

        // Show all result data for debugging
        $this->info('Full Result Data:');
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $this->line("  {$key}: " . json_encode($value, JSON_PRETTY_PRINT));
            } else {
                $this->line("  {$key}: {$value}");
            }
        }

        $this->newLine();
        $this->warn('=== TROUBLESHOOTING TIPS ===');
        $this->line('1. Check if the WhatsApp service URL is accessible');
        $this->line('2. Verify the API key is correct');
        $this->line('3. Ensure the service supports the required endpoints');
        $this->line('4. Check network connectivity to the WhatsApp service');
        $this->line('5. Review the logs for more detailed error information');
        $this->newLine();
    }
}
