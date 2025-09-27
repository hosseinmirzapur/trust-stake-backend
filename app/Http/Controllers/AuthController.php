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
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json();
    }

    /**
     * @param RegisterDetailsRequest $request
     * @return JsonResponse
     */
    public function registerDetails(RegisterDetailsRequest $request): JsonResponse
    {
        return response()->json(
            $this->authService->registerDetails($request->validated())
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
}
