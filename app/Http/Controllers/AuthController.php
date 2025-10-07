<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterDetailsRequest;
use App\Http\Requests\SignupRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Random\RandomException;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {

    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws RandomException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json(
            $this->authService->login($request->validated())
        );
    }

    /**
     * @param SignupRequest $request
     * @return JsonResponse
     */
    public function signup(SignupRequest $request): JsonResponse
    {
        return response()->json(
            $this->authService->signup($request->validated())
        );
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function registerDetails(Request $request): JsonResponse
    {
        return response()->json(
            $this->authService->registerDetails($request->validate([
                'token' => 'required|string',
                'name' => 'required|string|max:255',
                'country' => 'required|string|max:2',
                'email' => 'nullable|email',
                'mobile' => 'nullable|string',
                'credential' => 'required|string',
            ]))
        );
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(
            [
                'user' => $request->user(),
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        return response()->json(
            $this->authService->verifyOtp($request->validate([
                'otp' => 'required|string|size:6',
                'type' => 'required|string|in:login,signup',
                'credential' => 'required|string',
            ]))
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return response()->json(
            $this->authService->resendOtp($request->validate([
                'email' => 'required_without:mobile|string|email',
                'mobile' => 'required_without:email|string',
            ]))
        );
    }
}
