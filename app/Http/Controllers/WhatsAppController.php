<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    private WhatsAppService $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
    }

    /**
     * Start WhatsApp session
     */
    public function startSession(): JsonResponse
    {
        try {
            $result = $this->whatsappService->startSession();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to start WhatsApp session', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start session',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop WhatsApp session
     */
    public function stopSession(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->stopSession();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to stop WhatsApp session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to stop session',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart WhatsApp session
     */
    public function restartSession(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->restartSession();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to restart WhatsApp session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to restart session',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate WhatsApp session
     */
    public function terminateSession(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->terminateSession();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to terminate WhatsApp session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to terminate session',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session status
     */
    public function getSessionStatus(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->getSessionStatus();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp session status', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get session status',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get QR code for session authentication
     */
    public function getQRCode(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->getQRCode();

            if ($result['success'] && isset($result['image_data'])) {
                return response()->json([
                    'success' => true,
                    'image_data' => $result['image_data'],
                    'content_type' => $result['content_type'] ?? 'image/png'
                ]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp QR code', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get QR code',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request pairing code for session authentication
     */
    public function requestPairingCode(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $validated = $request->validate([
                'phoneNumber' => 'required|string',
                'showNotification' => 'boolean'
            ]);

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->requestPairingCode($validated['phoneNumber']);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to request WhatsApp pairing code', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to request pairing code',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get page screenshot for debugging
     */
    public function getPageScreenshot(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $this->whatsappService = new WhatsAppService($sessionId);
            $result = $this->whatsappService->getPageScreenshot();

            if ($result['success'] && isset($result['image_data'])) {
                return response()->json([
                    'success' => true,
                    'image_data' => $result['image_data'],
                    'content_type' => $result['content_type'] ?? 'image/png'
                ]);
            }

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp page screenshot', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get page screenshot',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all sessions
     */
    public function getAllSessions(Request $request): JsonResponse
    {
        try {
            $result = $this->whatsappService->getAllSessions();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get sessions',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate all inactive sessions
     */
    public function terminateInactiveSessions(Request $request): JsonResponse
    {
        try {
            $result = $this->whatsappService->terminateInactiveSessions();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to terminate inactive WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to terminate inactive sessions',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate all sessions
     */
    public function terminateAllSessions(Request $request): JsonResponse
    {
        try {
            $result = $this->whatsappService->terminateAllSessions();

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Failed to terminate all WhatsApp sessions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to terminate all sessions',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send message via WhatsApp
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->route('sessionId');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session ID is required'
                ], 400);
            }

            $validated = $request->validate([
                'chatId' => 'required|string',
                'message' => 'required|string',
                'text' => 'sometimes|string' // Alternative to message
            ]);

            $this->whatsappService = new WhatsAppService($sessionId);

            // Extract phone number from chatId (format: 98xxxxxxxxxx@c.us)
            $chatId = $validated['chatId'];
            if (preg_match('/(\d+)@c\.us/', $chatId, $matches)) {
                $phoneNumber = $matches[1];
                $messageText = $validated['message'] ?? $validated['text'] ?? '';

                $result = $this->whatsappService->sendOtp($phoneNumber, $messageText);

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'error' => 'Invalid chatId format'
            ], 400);
        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send message',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
