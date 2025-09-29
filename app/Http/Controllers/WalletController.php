<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * @return JsonResponse
     */
    public function balance(): JsonResponse
    {
        return response()->json(
            $this->walletService->balance()
        );
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $data = $request->validated();
        return response()->json(
            $this->walletService->deposit($data)
        );
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json(
            $this->walletService->withdraw($data)
        );
    }
}
