<?php

namespace App\Http\Controllers;

use App\Services\PlanService;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json($this->planService->all());
    }
}
