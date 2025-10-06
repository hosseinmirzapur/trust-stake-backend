<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

class PlanService
{
    /**
     * @return array{plans: Collection}
     */
    public function all(): array
    {
        $plans = Plan::query()->where('disabled', false)->get();
        return [
            'plans' => $plans,
        ];
    }
}