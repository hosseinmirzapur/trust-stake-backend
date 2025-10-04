<?php

namespace App\Http\Controllers;


use App\Http\Requests\ModifyProfileRequest;
use App\Http\Requests\Verify2FARequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Random\RandomException;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            $this->service->index()
        );
    }

    /**
     * @return JsonResponse
     */
    public function plans(): JsonResponse
    {
        return response()->json(
            $this->service->plans()
        );
    }

    /**
     * @return JsonResponse
     */
    public function subscriptions(): JsonResponse
    {
        return response()->json(
            $this->service->subscriptions()
        );
    }

    /**
     * @return JsonResponse
     */
    public function wallet(): JsonResponse
    {
        return response()->json(
            $this->service->wallet()
        );
    }

    /**
     * @param ModifyProfileRequest $request
     * @return JsonResponse
     */
    public function modifyProfile(ModifyProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        return response()->json(
            $this->service->modifyProfile($data)
        );
    }

    /**
     * @return JsonResponse
     * @throws RandomException
     */
    public function sendEmailVerificationCode(): JsonResponse
    {
        return response()->json(
            $this->service->sendEmailVerificationCode()
        );
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $data = $request->validated();
        return response()->json(
            $this->service->verifyEmail($data)
        );
    }

    /**
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function activate2FA(): JsonResponse
    {
        return response()->json(
            $this->service->activate2FA()
        );
    }

    /**
     * @param Verify2FARequest $request
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verify2FA(Verify2FARequest $request): JsonResponse
    {
        $data = $request->validated();
        return response()->json(
            $this->service->verify2FA($data)
        );
    }

    /**
     * @return JsonResponse
     */
    public function referral(): JsonResponse
    {
        return response()->json(
            $this->service->referral()
        );
    }
}
