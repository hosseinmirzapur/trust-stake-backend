<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    private string $apiKey;
    private string $baseUrl;
    private string $sessionId;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key');
        $this->baseUrl = config('services.whatsapp.api_url', 'https://api.whatsapp-plus.com');
        // $this->sessionId = config('services.whatsapp.session_id', 'truststake-session');
        $this->sessionId = Str::uuid();

        if (!$this->apiKey) {
            Log::error('WhatsApp API key not configured');
        }
    }

    /**
     * Start a new WhatsApp session
     */
    public function startSession(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/session/start/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp session started successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);
                return $data;
            }

            Log::error('Failed to start WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to start session',
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while starting WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get session status
     */
    public function getSessionStatus(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/session/status/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();

                // If session not found, try to start it first
                if (isset($data['message']) && $data['message'] === 'session_not_found') {
                    Log::info('Session not found, attempting to start session', [
                        'session_id' => $this->sessionId
                    ]);

                    $startResult = $this->startSession();
                    if ($startResult['success']) {
                        // Wait a moment then try to get status again
                        sleep(2);
                        return $this->getSessionStatus();
                    }

                    return $startResult;
                }

                return $data;
            }

            // If we get a 404 or session not found, try to start the session
            if ($response->status() === 404) {
                Log::info('Session not found (404), attempting to start session', [
                    'session_id' => $this->sessionId
                ]);

                $startResult = $this->startSession();
                if ($startResult['success']) {
                    sleep(2);
                    return $this->getSessionStatus();
                }

                return $startResult;
            }

            return [
                'success' => false,
                'error' => 'Failed to get session status',
                'status_code' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code for session authentication
     */
    public function getQRCode(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey
            ])->get("{$this->baseUrl}/session/qr/{$this->sessionId}");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'error' => 'Failed to get QR code',
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get QR code as image
     */
    public function getQRCodeImage(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey
            ])->get("{$this->baseUrl}/session/qr/{$this->sessionId}/image");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'image_data' => base64_encode($response->body()),
                    'content_type' => 'image/png'
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get QR code image',
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop WhatsApp session
     */
    public function stopSession(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey
            ])->get("{$this->baseUrl}/session/stop/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp session stopped successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);
                return $data;
            }

            Log::error('Failed to stop WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to stop session'
            ];
        } catch (\Exception $e) {
            Log::error('Exception while stopping WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Send OTP message via WhatsApp
     */
    public function sendOtp(string $phoneNumber, string $otpCode): array
    {
        try {
            // Ensure phone number is in correct format (without +)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Check if session is ready
            $statusResponse = $this->getSessionStatus();
            if (!$statusResponse['success'] || !isset($statusResponse['state']) || $statusResponse['state'] !== 'CONNECTED') {
                Log::warning('WhatsApp session not ready for sending OTP', [
                    'session_id' => $this->sessionId,
                    'status' => $statusResponse
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not ready'
                ];
            }

            $message = "Your TrustStake OTP code is: {$otpCode}. This code will expire in 5 minutes.";

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/client/sendMessage/{$this->sessionId}", [
                'chatId' => "{$cleanPhone}@c.us",
                'contentType' => 'string',
                'content' => $message
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId
                ]);
                return $data;
            }

            Log::error('Failed to send WhatsApp OTP', [
                'phone' => $phoneNumber,
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send OTP',
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while sending WhatsApp OTP', [
                'phone' => $phoneNumber,
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if WhatsApp session is ready
     */
    public function isSessionReady(): bool
    {
        $status = $this->getSessionStatus();

        // If session status check initiated a new session, it might not be ready yet
        // but we consider the service as "working"
        if ($status['success'] && isset($status['state'])) {
            return $status['state'] === 'CONNECTED';
        }

        // If session doesn't exist but we can start it, consider it as "service available"
        return isset($status['message']) && $status['message'] === 'session_not_found' ? false : $status['success'];
    }

    /**
     * Initialize WhatsApp session if not ready
     */
    public function initializeSession(): array
    {
        if (!$this->isSessionReady()) {
            Log::info('Initializing WhatsApp session', ['session_id' => $this->sessionId]);
            return $this->startSession();
        }

        return ['success' => true, 'message' => 'Session already ready'];
    }

    /**
     * Test the WhatsApp service connection and OTP sending
     */
    public function testService(string $testPhoneNumber = null): array
    {
        try {
            // Test session status
            $statusResult = $this->getSessionStatus();
            Log::info('WhatsApp service test - session status', [
                'session_id' => $this->sessionId,
                'status' => $statusResult
            ]);

            if (!$statusResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Cannot get session status',
                    'details' => $statusResult
                ];
            }

            // Try to initialize session if not ready
            $initResult = $this->initializeSession();
            if (!$initResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Session initialization failed',
                    'details' => $initResult,
                    'session_status' => $statusResult
                ];
            }

            // If test phone number provided, try sending a test OTP
            if ($testPhoneNumber) {
                $testOtp = '123456'; // Test OTP
                $otpResult = $this->sendOtp($testPhoneNumber, $testOtp);

                return [
                    'success' => $otpResult['success'],
                    'message' => $otpResult['success'] ? 'Test OTP sent successfully' : 'Test OTP failed',
                    'session_status' => $statusResult,
                    'session_init' => $initResult,
                    'otp_test' => $otpResult
                ];
            }

            return [
                'success' => true,
                'message' => 'WhatsApp service test completed successfully',
                'session_status' => $statusResult,
                'session_init' => $initResult
            ];
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp service test', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during test',
                'details' => $e->getMessage()
            ];
        }
    }
}
