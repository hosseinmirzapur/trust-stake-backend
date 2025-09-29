<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuySubscriptionRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $service)
    {
    }

    /**
     * @param BuySubscriptionRequest $request
     * @param int $plan_id
     * @return JsonResponse
     */
    public function buy(BuySubscriptionRequest $request, int $plan_id): JsonResponse
    {
        $data = $request->validated();

        return response()->json(
            $this->service->buy($data, $plan_id)
        );
    }

    /**
     * @return JsonResponse
     */
    public function my(): JsonResponse
    {
        return response()->json(
            $this->service->my()
        );
    }
}
