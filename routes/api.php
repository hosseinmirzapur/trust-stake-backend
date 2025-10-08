<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

# Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('register-details', [AuthController::class, 'registerDetails'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
});

# Wallet
Route::prefix('wallet')->group(function () {
    Route::get('/', [WalletController::class, 'balance']);
    Route::post('/deposit', [WalletController::class, 'deposit']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
});

# Plans and Subscription
Route::middleware('auth:sanctum')->get('/plans', [PlanController::class, 'index']);
Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::post('/buy/{plan_id}', [SubscriptionController::class, 'buy']);
    Route::get('/my', [SubscriptionController::class, 'my']);
});

# Tickets
Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']);
    Route::post('/', [TicketController::class, 'store']);
    Route::post('/{ticket_id}/reply', [TicketController::class, 'reply']);
    Route::post('/{ticket_id}/close', [TicketController::class, 'close']);
});


# Payment Callbacks
Route::prefix('payment')->group(function () {
    Route::post('/callback', [PaymentController::class, 'handlePaymentCallback']);
    Route::post('/withdrawal/callback', [PaymentController::class, 'handleWithdrawalCallback']);
    Route::get('/success', [PaymentController::class, 'paymentSuccess']);
});

# WhatsApp API Routes
Route::prefix('whatsapp')->group(function () {
    Route::get('/session/start', [WhatsAppController::class, 'startSession']);
    Route::get('/session/start/{sessionId}', [WhatsAppController::class, 'startSession']);
    Route::get('/session/stop/{sessionId}', [WhatsAppController::class, 'stopSession']);
    Route::get('/session/restart/{sessionId}', [WhatsAppController::class, 'restartSession']);
    Route::get('/session/terminate/{sessionId}', [WhatsAppController::class, 'terminateSession']);
    Route::get('/session/status/{sessionId}', [WhatsAppController::class, 'getSessionStatus']);
    Route::get('/session/qr/{sessionId}/image', [WhatsAppController::class, 'getQRCode']);
    Route::post('/session/requestPairingCode/{sessionId}', [WhatsAppController::class, 'requestPairingCode']);
    Route::get('/session/getPageScreenshot/{sessionId}', [WhatsAppController::class, 'getPageScreenshot']);
    Route::get('/session/getSessions', [WhatsAppController::class, 'getAllSessions']);
    Route::get('/session/terminateInactive', [WhatsAppController::class, 'terminateInactiveSessions']);
    Route::get('/session/terminateAll', [WhatsAppController::class, 'terminateAllSessions']);
    Route::post('/client/sendMessage/{sessionId}', [WhatsAppController::class, 'sendMessage']);
});

# Dashboard
Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/subscriptions', [DashboardController::class, 'subscriptions']);
    Route::get('/wallet', [DashboardController::class, 'wallet']);
    Route::get('/profile', [DashboardController::class, 'profile']);

    Route::post('profile/modify', [DashboardController::class, 'modifyProfile']);
    Route::post('profile/send-email-verification-code', [DashboardController::class, 'sendEmailVerificationCode']);
    Route::post('profile/verify-email', [DashboardController::class, 'verifyEmail']);
    Route::post('2fa/activate', [DashboardController::class, 'activate2FA']);
    Route::post('2fa/verify', [DashboardController::class, 'verify2FA']);
    Route::get('referral', [DashboardController::class, 'referral']);
});
