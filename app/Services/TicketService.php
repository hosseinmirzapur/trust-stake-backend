<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketService
{
    public function index(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $tickets = $user->tickets;

        return [
            'tickets' => $tickets,
        ];
    }

    public function store(array $data): array
    {
        /** @var User $user */
        $user = auth()->user();

        if ($data['image']) {
            $path = 'tickets';
            /** @var UploadedFile $file */
            $file = $data['image'];
            $fileName = $this->generateFileName($file);
            Storage::putFileAs($path, $file, $fileName);

            $data['image'] = $path . '/' . $fileName;
        }

        $ticket = $user->tickets()->create([
            'title' => $data['title'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'image' => $data['image'] ?? null,
        ]);

        return [
            'ticket' => $ticket,
        ];
    }

    public function reply(array $data, int $ticket_id): array
    {
        /** @var User $user */
        $user = auth()->user();
        $ticket = $user->tickets()->find($ticket_id);
        abort_if(!$ticket, 404, 'Ticket not found');

        if ($data['image']) {
            $path = 'tickets/' . $ticket_id . '/reply';
            /** @var UploadedFile $file */
            $file = $data['image'];
            $fileName = $this->generateFileName($file);
            Storage::putFileAs($path, $file, $fileName);
            $data['image'] = $path . '/' . $fileName;
        }

        $ticket = Ticket::query()->create([
            'title' => $data['title'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'image' => $data['image'] ?? null,
            'ticket_id' => $ticket_id,
        ]);

        return [
            'ticket' => $ticket,
        ];
    }

    private function generateFileName(UploadedFile $file): string
    {
        return Str::random(20) . '.' . $file->getClientOriginalExtension();
    }
}