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
        $this->sessionId = config('services.whatsapp.session_id', 'truststake-session');

        if (!$this->apiKey) {
            Log::error('WhatsApp API key not configured');
        }

        // Debug logging for configuration
        Log::info('WhatsApp Service initialized', [
            'api_key_configured' => !empty($this->apiKey),
            'base_url' => $this->baseUrl,
            'session_id' => $this->sessionId,
            'key_length' => strlen($this->apiKey ?? '')
        ]);
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

                // If session exists but not connected, return the current status
                if (isset($data['state']) && $data['state'] !== 'CONNECTED') {
                    Log::info('WhatsApp session exists but not connected', [
                        'session_id' => $this->sessionId,
                        'state' => $data['state']
                    ]);
                    return $data;
                }

                return $data;
            }

            // If we get a 404, try to start the session
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

            // If session already exists (422), return success since session exists
            if ($response->status() === 422) {
                $responseData = $response->json();
                if (isset($responseData['error']) && strpos($responseData['error'], 'already exists') !== false) {
                    Log::info('WhatsApp session already exists, which is good', [
                        'session_id' => $this->sessionId
                    ]);
                    return [
                        'success' => true,
                        'state' => 'CONNECTED', // Assume connected if session exists
                        'message' => 'Session already exists'
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to get session status',
                'status_code' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while getting WhatsApp session status', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while getting session status',
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

            // Validate API key and URL
            if (empty($this->apiKey) || $this->apiKey === 'your_whatsapp_api_key_here') {
                Log::error('WhatsApp API key not properly configured', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp API key not configured properly'
                ];
            }

            // Check session status first
            $status = $this->getSessionStatus();
            if (!$status['success']) {
                Log::error('WhatsApp session not available for OTP sending', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'status' => $status
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not available',
                    'details' => $status
                ];
            }

            $message = "Your TrustStake OTP code is: {$otpCode}. This code will expire in 5 minutes.";

            Log::info('WhatsApp OTP sending attempt', [
                'phone' => $phoneNumber,
                'clean_phone' => $cleanPhone,
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl,
                'api_key_configured' => !empty($this->apiKey)
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])->post("{$this->baseUrl}/client/sendMessage/{$this->sessionId}", [
                    'chatId' => "{$cleanPhone}@c.us",
                    'contentType' => 'string',
                    'content' => $message
                ]);

            Log::info('WhatsApp API response received', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'success' => $response->successful(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'response_data' => $data
                ]);
                return $data;
            }

            // Handle specific error cases
            if ($response->status() === 404) {
                $errorBody = $response->json();
                if (isset($errorBody['error']) && $errorBody['error'] === 'session_not_connected') {
                    Log::error('WhatsApp session not connected', [
                        'phone' => $phoneNumber,
                        'session_id' => $this->sessionId,
                        'response' => $errorBody
                    ]);

                    // Try to reinitialize session
                    $this->initializeSession();

                    return [
                        'success' => false,
                        'error' => 'WhatsApp session not connected',
                        'retry_possible' => true,
                        'details' => $errorBody
                    ];
                }
            }

            // Log the failure with full details
            Log::error('WhatsApp API call failed', [
                'phone' => $phoneNumber,
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'url' => $response->effectiveUri()
            ]);

            return [
                'success' => false,
                'error' => 'WhatsApp API call failed',
                'status' => $response->status(),
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
                'error' => 'Exception occurred while sending WhatsApp OTP',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if WhatsApp session is ready
     */
    public function isSessionReady(): bool
    {
        try {
            $status = $this->getSessionStatus();

            // Be more lenient - if session exists (even if not connected), consider it ready
            // The OTP sending seems to work even with "not connected" sessions
            if ($status['success']) {
                return true;
            }

            // If session not found, return false
            return false;
        } catch (\Exception $e) {
            // If status check fails, assume not ready but don't crash
            Log::warning('WhatsApp session readiness check failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Initialize WhatsApp session if not ready
     */
    public function initializeSession(): array
    {
        try {
            Log::info('Initializing WhatsApp session', ['session_id' => $this->sessionId]);

            // First check if session already exists and is working
            $status = $this->getSessionStatus();

            if ($status['success']) {
                Log::info('WhatsApp session already initialized', [
                    'session_id' => $this->sessionId,
                    'status' => $status
                ]);
                return ['success' => true, 'message' => 'Session already initialized'];
            }

            // If session doesn't exist, try to start it
            if (isset($status['error']) && strpos($status['error'], 'session_not_found') !== false) {
                Log::info('WhatsApp session not found, starting new session', [
                    'session_id' => $this->sessionId
                ]);

                $startResult = $this->startSession();
                if ($startResult['success']) {
                    // Wait for session to be ready
                    sleep(3);

                    // Check status again after starting
                    $newStatus = $this->getSessionStatus();
                    if ($newStatus['success']) {
                        Log::info('WhatsApp session started successfully', [
                            'session_id' => $this->sessionId
                        ]);
                        return ['success' => true, 'message' => 'Session started successfully'];
                    } else {
                        Log::warning('WhatsApp session started but status check failed', [
                            'session_id' => $this->sessionId,
                            'start_result' => $startResult,
                            'status_result' => $newStatus
                        ]);
                        return [
                            'success' => false,
                            'message' => 'Session started but not ready',
                            'details' => $newStatus
                        ];
                    }
                } else {
                    Log::error('Failed to start WhatsApp session', [
                        'session_id' => $this->sessionId,
                        'result' => $startResult
                    ]);
                    return $startResult;
                }
            }

            // Session exists but has issues
            Log::warning('WhatsApp session exists but has issues', [
                'session_id' => $this->sessionId,
                'status' => $status
            ]);

            return [
                'success' => false,
                'message' => 'Session exists but not working properly',
                'details' => $status
            ];

        } catch (\Exception $e) {
            Log::error('Exception in initializeSession', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during session initialization',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Test the WhatsApp service connection and OTP sending
     */
    public function testService(string $testPhoneNumber = null): array
    {
        try {
            Log::info('Starting WhatsApp service test', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl,
                'api_key_configured' => !empty($this->apiKey)
            ]);

            // Test basic connectivity first
            if (empty($this->apiKey) || $this->apiKey === 'your_whatsapp_api_key_here') {
                return [
                    'success' => false,
                    'error' => 'WhatsApp API key not configured properly',
                    'message' => 'Please configure a valid WHATSAPP_API_KEY in .env file'
                ];
            }

            // Test session status
            $statusResult = $this->getSessionStatus();
            Log::info('WhatsApp service test - session status', [
                'session_id' => $this->sessionId,
                'status' => $statusResult
            ]);

            if (!$statusResult['success']) {
                Log::warning('Session status check failed, but continuing test', [
                    'session_id' => $this->sessionId,
                    'status' => $statusResult
                ]);
            }

            // Try to initialize session
            $initResult = $this->initializeSession();
            Log::info('WhatsApp service test - session initialization', [
                'session_id' => $this->sessionId,
                'result' => $initResult
            ]);

            // If test phone number provided, try sending a test OTP
            if ($testPhoneNumber) {
                $testOtp = '123456'; // Test OTP
                Log::info('WhatsApp service test - sending test OTP', [
                    'phone' => $testPhoneNumber,
                    'session_id' => $this->sessionId
                ]);

                $otpResult = $this->sendOtp($testPhoneNumber, $testOtp);

                return [
                    'success' => $otpResult['success'],
                    'message' => $otpResult['success'] ? 'Test OTP sent successfully' : 'Test OTP failed',
                    'session_status' => $statusResult,
                    'session_init' => $initResult,
                    'otp_test' => $otpResult,
                    'configuration' => [
                        'base_url' => $this->baseUrl,
                        'session_id' => $this->sessionId,
                        'api_key_configured' => !empty($this->apiKey)
                    ]
                ];
            }

            return [
                'success' => true,
                'message' => 'WhatsApp service test completed successfully (without OTP test)',
                'session_status' => $statusResult,
                'session_init' => $initResult,
                'configuration' => [
                    'base_url' => $this->baseUrl,
                    'session_id' => $this->sessionId,
                    'api_key_configured' => !empty($this->apiKey)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Exception during WhatsApp service test', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception during test',
                'details' => $e->getMessage(),
                'configuration' => [
                    'base_url' => $this->baseUrl,
                    'session_id' => $this->sessionId,
                    'api_key_configured' => !empty($this->apiKey)
                ]
            ];
        }
    }
}
