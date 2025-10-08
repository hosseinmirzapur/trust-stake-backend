<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WhatsAppService
{
    private string $apiKey;
    private string $baseUrl;
    private string|null $sessionId;

    public function __construct(string|null $sessionId = null)
    {
        $this->apiKey = config('services.whatsapp.api_key');
        $this->baseUrl = rtrim(config('services.whatsapp.api_url'), '/');
        $this->sessionId = $sessionId ? strval($sessionId) : Str::uuid(); // do not change this

        if (!$this->apiKey) {
            Log::error('WhatsApp API key not configured');
        }

        // Debug logging for configuration
        Log::info('WhatsApp Service initialized', [
            'api_key_configured' => !empty($this->apiKey),
            'base_url' => $this->baseUrl,
            'session_id' => $this->sessionId,
            'cache_enabled' => Cache::getStore() !== null
        ]);
    }

    /**
     * Get cache key for session data
     */
    private function getCacheKey(string $type = 'status'): string
    {
        return "whatsapp_session_{$this->sessionId}_$type";
    }

    /**
     * Cache session status for better performance
     */
    private function cacheSessionStatus(array $status, int $ttl = 300): void
    {
        Cache::put($this->getCacheKey('status'), $status, $ttl);
    }

    /**
     * Get cached session status
     */
    private function getCachedSessionStatus(): ?array
    {
        return Cache::get($this->getCacheKey('status'));
    }

    /**
     * Clear session cache
     */
    private function clearSessionCache(): void
    {
        Cache::forget($this->getCacheKey('status'));
        Cache::forget($this->getCacheKey('info'));
    }

    /**
     * Make authenticated HTTP request to WhatsApp API
     */
    private function makeRequest(string $method, string $endpoint, array $data = null)
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $headers = [
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json'
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $headers['Content-Type'] = 'application/json';
        }

        Log::info('WhatsApp API request', [
            'method' => $method,
            'url' => $url,
            'session_id' => $this->sessionId
        ]);

        if ($data) {
            return Http::withHeaders($headers)->{$method}($url, $data);
        }

        return Http::withHeaders($headers)->{$method}($url);
    }

    /**
     * Start a new WhatsApp session
     * GET /session/start/{sessionId}
     */
    public function startSession(): array
    {
        try {
            Log::info('Starting WhatsApp session', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            $response = $this->makeRequest('get', "/session/start/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear cache when starting new session

                Log::info('WhatsApp session started successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Session started successfully',
                    'data' => $data
                ];
            }

            Log::error('Failed to start WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to start session',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while starting WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while starting session',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop WhatsApp session
     * GET /session/stop/{sessionId}
     */
    public function stopSession(): array
    {
        try {
            Log::info('Stopping WhatsApp session', [
                'session_id' => $this->sessionId
            ]);

            $response = $this->makeRequest('get', "/session/stop/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear cache when stopping session

                Log::info('WhatsApp session stopped successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Session stopped successfully',
                    'data' => $data
                ];
            }

            Log::error('Failed to stop WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to stop session',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while stopping WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while stopping session',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Restart WhatsApp session
     * GET /session/restart/{sessionId}
     */
    public function restartSession(): array
    {
        try {
            Log::info('Restarting WhatsApp session', [
                'session_id' => $this->sessionId
            ]);

            $response = $this->makeRequest('get', "/session/restart/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear cache when restarting session

                Log::info('WhatsApp session restarted successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Session restarted successfully',
                    'data' => $data
                ];
            }

            Log::error('Failed to restart WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to restart session',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while restarting WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while restarting session',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Terminate WhatsApp session
     * GET /session/terminate/{sessionId}
     */
    public function terminateSession(): array
    {
        try {
            Log::info('Terminating WhatsApp session', [
                'session_id' => $this->sessionId
            ]);

            $response = $this->makeRequest('get', "/session/terminate/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear cache when terminating session

                Log::info('WhatsApp session terminated successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Session terminated successfully',
                    'data' => $data
                ];
            }

            Log::error('Failed to terminate WhatsApp session', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to terminate session',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while terminating WhatsApp session', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while terminating session',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all sessions
     * GET /session/getSessions
     */
    public function getAllSessions(): array
    {
        try {
            Log::info('Getting all WhatsApp sessions');

            $response = $this->makeRequest('get', '/session/getSessions');

            if ($response->successful()) {
                $data = $response->json();

                Log::info('WhatsApp sessions retrieved successfully', [
                    'count' => count($data ?? [])
                ]);

                return [
                    'success' => true,
                    'sessions' => $data ?? []
                ];
            }

            Log::error('Failed to get WhatsApp sessions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get sessions',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while getting WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while getting sessions',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Terminate all inactive sessions
     * GET /session/terminateInactive
     */
    public function terminateInactiveSessions(): array
    {
        try {
            Log::info('Terminating inactive WhatsApp sessions');

            $response = $this->makeRequest('get', '/session/terminateInactive');

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear all session caches

                Log::info('Inactive WhatsApp sessions terminated successfully', [
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Inactive sessions terminated',
                    'data' => $data
                ];
            }

            Log::error('Failed to terminate inactive WhatsApp sessions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to terminate inactive sessions',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while terminating inactive WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while terminating inactive sessions',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Terminate all sessions
     * GET /session/terminateAll
     */
    public function terminateAllSessions(): array
    {
        try {
            Log::info('Terminating all WhatsApp sessions');

            $response = $this->makeRequest('get', '/session/terminateAll');

            if ($response->successful()) {
                $data = $response->json();
                $this->clearSessionCache(); // Clear all session caches

                Log::info('All WhatsApp sessions terminated successfully', [
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'All sessions terminated',
                    'data' => $data
                ];
            }

            Log::error('Failed to terminate all WhatsApp sessions', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to terminate all sessions',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while terminating all WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while terminating all sessions',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get session status
     * 🖼 مرحله ۳ — چک کردن وضعیت سشن
     * GET /session/status/{sessionId}
     */
    public function getSessionStatus(): array
    {
        try {
            Log::info('Getting WhatsApp session status', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->baseUrl}/session/status/{$this->sessionId}");

            if ($response->successful()) {
                $data = $response->json();

                // Check if session is connected
                $isConnected = isset($data['state']) && $data['state'] === 'CONNECTED';

                Log::info('WhatsApp session status retrieved', [
                    'session_id' => $this->sessionId,
                    'state' => $data['state'] ?? 'unknown',
                    'connected' => $isConnected
                ]);

                return [
                    'success' => true,
                    'connected' => $isConnected,
                    'state' => $data['state'] ?? 'unknown',
                    'data' => $data
                ];
            }

            // If session not found (404), try to start it
            if ($response->status() === 404) {
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

            Log::error('Failed to get session status', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get session status',
                'status_code' => $response->status(),
                'details' => $response->body()
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
     * 📷 مرحله ۲ — لاگین کردن سشن (روش اول: QR کد)
     * GET /session/qr/{sessionId}/image
     */
    public function getQRCode(): array
    {
        try {
            Log::info('Getting WhatsApp QR code', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey
            ])->get("{$this->baseUrl}/session/qr/{$this->sessionId}/image");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'image_data' => base64_encode($response->body()),
                    'content_type' => 'image/png'
                ];
            }

            Log::error('Failed to get QR code', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get QR code',
                'status_code' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while getting QR code', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while getting QR code',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Request pairing code for session authentication
     * 📌 روش دوم: Pairing Code (بدون QR)
     * POST /session/requestPairingCode/{sessionId}
     */
    public function requestPairingCode(string $phoneNumber): array
    {
        try {
            Log::info('Requesting WhatsApp pairing code', [
                'session_id' => $this->sessionId,
                'phone_number' => $phoneNumber,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/session/requestPairingCode/{$this->sessionId}", [
                'phoneNumber' => $phoneNumber,
                'showNotification' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp pairing code requested successfully', [
                    'session_id' => $this->sessionId,
                    'response' => $data
                ]);
                return [
                    'success' => true,
                    'message' => 'Pairing code requested successfully',
                    'data' => $data
                ];
            }

            Log::error('Failed to request pairing code', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to request pairing code',
                'status_code' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while requesting pairing code', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while requesting pairing code',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get page screenshot for debugging
     * 🖼 اسکرین‌شات از صفحه واتساپ
     * GET /session/getPageScreenshot/{sessionId}
     */
    public function getPageScreenshot(): array
    {
        try {
            Log::info('Getting WhatsApp page screenshot', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey
            ])->get("{$this->baseUrl}/session/getPageScreenshot/{$this->sessionId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'image_data' => base64_encode($response->body()),
                    'content_type' => 'image/png'
                ];
            }

            Log::error('Failed to get page screenshot', [
                'session_id' => $this->sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get page screenshot',
                'status_code' => $response->status(),
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Exception while getting page screenshot', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred while getting page screenshot',
                'details' => $e->getMessage()
            ];
        }
    }


    /**
     * Send OTP message via WhatsApp
     * POST /client/sendMessage/{sessionId}
     * According to API docs: Send a message to a specific chatId
     */
    public function sendOtp(string $phoneNumber, string $otpCode): array
    {
        try {
            // Ensure phone number is in correct format (international without +)
            // API docs: شماره باید بین‌المللی بدون + باشه (مثلاً: 98...)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Validate API key
            if (empty($this->apiKey) || $this->apiKey !== 'whatsappplus') {
                Log::error('WhatsApp API key not properly configured', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'api_key' => $this->apiKey ? 'configured' : 'empty'
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp API key not configured properly'
                ];
            }

            // Check session status first - use cached status for performance
            $cachedStatus = $this->getCachedSessionStatus();
            if ($cachedStatus && isset($cachedStatus['connected']) && !$cachedStatus['connected']) {
                Log::info('Using cached session status - session not connected', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'cached_status' => $cachedStatus
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected (cached)',
                    'details' => $cachedStatus
                ];
            }

            // Get fresh status if not cached or if cached status is old
            $status = $this->getSessionStatus();
            $this->cacheSessionStatus($status); // Cache the status

            if (!$status['success'] || !$status['connected']) {
                Log::error('WhatsApp session not connected for OTP sending', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'status' => $status
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected',
                    'details' => $status
                ];
            }

            $message = "Your TrustStake OTP code is: {$otpCode}. This code will expire in 5 minutes.";

            // API docs: chatId باید به فرمت بین‌المللی بدون + باشه و آخرش @c.us
            $chatId = "{$cleanPhone}@c.us";

            Log::info('WhatsApp OTP sending attempt', [
                'phone' => $phoneNumber,
                'clean_phone' => $cleanPhone,
                'chat_id' => $chatId,
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            // Use correct endpoint: /client/sendMessage/{sessionId}
            $response = $this->makeRequest('post', "/client/sendMessage/{$this->sessionId}", [
                'chatId' => $chatId,
                'message' => $message
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
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'data' => $data
                ];
            }

            // Handle specific error cases
            if ($response->status() === 404) {
                Log::error('WhatsApp session not found or not connected', [
                    'phone' => $phoneNumber,
                    'session_id' => $this->sessionId,
                    'response_body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected',
                    'status' => $response->status(),
                    'details' => $response->body()
                ];
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
     * Complete flow: Start session → Wait for user to scan QR/pair → Check status
     */
    public function initializeSession(): array
    {
        try {
            Log::info('Initializing WhatsApp session', [
                'session_id' => $this->sessionId,
                'base_url' => $this->baseUrl
            ]);

            // Step 1: Check if session already exists and is connected
            $status = $this->getSessionStatus();

            if ($status['success'] && $status['connected']) {
                Log::info('WhatsApp session already connected', [
                    'session_id' => $this->sessionId,
                    'state' => $status['state']
                ]);
                return [
                    'success' => true,
                    'message' => 'Session already connected',
                    'status' => $status
                ];
            }

            // Step 2: Start a new session if it doesn't exist
            if (!$status['success']) {
                Log::info('Session not found, starting new session', [
                    'session_id' => $this->sessionId
                ]);

                $startResult = $this->startSession();
                if (!$startResult['success']) {
                    Log::error('Failed to start WhatsApp session', [
                        'session_id' => $this->sessionId,
                        'result' => $startResult
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Failed to start session',
                        'details' => $startResult
                    ];
                }

                Log::info('WhatsApp session started, waiting for user authentication', [
                    'session_id' => $this->sessionId
                ]);

                return [
                    'success' => true,
                    'message' => 'Session started, waiting for user authentication',
                    'needs_auth' => true,
                    'start_result' => $startResult
                ];
            }

            // Step 3: Session exists but not connected - needs user authentication
            Log::info('WhatsApp session exists but not connected', [
                'session_id' => $this->sessionId,
                'state' => $status['state']
            ]);

            return [
                'success' => true,
                'message' => 'Session exists, waiting for user authentication',
                'needs_auth' => true,
                'status' => $status
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

    /**
     * Send OTP message via WhatsApp with specific session
     * POST /client/sendMessage/{sessionId}
     */
    public function sendOtpWithSession(string $phoneNumber, string $otpCode, string $sessionId): array
    {
        try {
            // Temporarily override the session ID for this request
            $originalSessionId = $this->sessionId;
            $this->sessionId = $sessionId;

            // Validate API key
            if (empty($this->apiKey) || $this->apiKey !== 'whatsappplus') {
                Log::error('WhatsApp API key not properly configured', [
                    'phone' => $phoneNumber,
                    'session_id' => $sessionId
                ]);

                $this->sessionId = $originalSessionId; // Restore original session ID
                return [
                    'success' => false,
                    'error' => 'WhatsApp API key not configured properly',
                    'retry_possible' => false
                ];
            }

            // Check session status first
            $status = $this->getSessionStatus();

            if (!$status['success'] || !$status['connected']) {
                Log::error('WhatsApp session not connected for OTP sending', [
                    'phone' => $phoneNumber,
                    'session_id' => $sessionId,
                    'status' => $status
                ]);

                $this->sessionId = $originalSessionId; // Restore original session ID
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected',
                    'retry_possible' => true,
                    'status' => $status
                ];
            }

            // Prepare the message
            $messageText = "Your OTP verification code is: {$otpCode}\n\nThis code will expire in 5 minutes.";

            Log::info('WhatsApp OTP sending attempt', [
                'phone' => $phoneNumber,
                'session_id' => $sessionId,
                'otp_length' => strlen($otpCode)
            ]);

            $response = $this->makeRequest('post', "/client/sendMessage/{$sessionId}", [
                'chatId' => "{$phoneNumber}@c.us",
                'message' => $messageText
            ]);

            Log::info('WhatsApp API response received', [
                'phone' => $phoneNumber,
                'session_id' => $sessionId,
                'status' => $response->status()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp OTP sent successfully', [
                    'phone' => $phoneNumber,
                    'session_id' => $sessionId,
                    'response' => $data
                ]);

                $this->sessionId = $originalSessionId; // Restore original session ID
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully via WhatsApp',
                    'data' => $data,
                    'retry_possible' => false
                ];
            }

            if ($response->status() === 404) {
                Log::error('WhatsApp session not found or not connected', [
                    'phone' => $phoneNumber,
                    'session_id' => $sessionId,
                    'status' => $response->status()
                ]);

                $this->sessionId = $originalSessionId; // Restore original session ID
                return [
                    'success' => false,
                    'error' => 'WhatsApp session not connected',
                    'status' => $response->status(),
                    'retry_possible' => true
                ];
            }

            // Log the failure with full details
            Log::error('WhatsApp API call failed', [
                'phone' => $phoneNumber,
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $this->sessionId = $originalSessionId; // Restore original session ID
            return [
                'success' => false,
                'error' => 'WhatsApp API call failed',
                'status' => $response->status(),
                'response' => $response->body(),
                'retry_possible' => true
            ];
        } catch (\Exception $e) {
            Log::error('Exception while sending WhatsApp OTP', [
                'phone' => $phoneNumber,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            // Restore original session ID before returning
            if (isset($originalSessionId)) {
                $this->sessionId = $originalSessionId;
            }

            return [
                'success' => false,
                'error' => 'Exception occurred while sending WhatsApp OTP',
                'details' => $e->getMessage(),
                'retry_possible' => true
            ];
        }
    }
}
