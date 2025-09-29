<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service)
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
     * @param StoreTicketRequest $request
     * @return JsonResponse
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json(
            $this->service->store($data)
        );
    }

    /**
     * @param StoreTicketRequest $request
     * @param int $ticket_id
     * @return JsonResponse
     */
    public function reply(StoreTicketRequest $request, int $ticket_id): JsonResponse
    {
        $data = $request->validated();
        return response()->json(
            $this->service->reply($data, $ticket_id)
        );
    }
}
