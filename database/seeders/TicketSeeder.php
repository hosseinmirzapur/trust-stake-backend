<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->take(5);

        foreach ($users as $user) {
            // Create main tickets
            Ticket::create([
                'user_id' => $user->id,
                'title' => 'Support Request - Account Issue',
                'subject' => 'Cannot access my account',
                'message' => 'I am having trouble logging into my account. The system says my credentials are incorrect even though I am sure they are right.',
                'status' => Ticket::STATUS_PENDING,
                'ticket_id' => null,
            ]);

            Ticket::create([
                'user_id' => $user->id,
                'title' => 'Feature Request - Dark Mode',
                'subject' => 'Request for dark mode theme',
                'message' => 'I would like to request a dark mode theme for the application. It would be much easier on the eyes during night time usage.',
                'status' => Ticket::STATUS_WAITING_FOR_RESPONSE,
                'ticket_id' => null,
            ]);

            // Create some follow-up tickets (replies)
            $mainTicket = Ticket::where('user_id', $user->id)->whereNull('ticket_id')->first();
            if ($mainTicket) {
                Ticket::create([
                    'user_id' => $user->id,
                    'title' => 'RE: Support Request - Account Issue',
                    'subject' => 'RE: Cannot access my account',
                    'message' => 'I have tried resetting my password but I am still having issues. Please help me resolve this as soon as possible.',
                    'status' => Ticket::STATUS_PENDING,
                    'ticket_id' => $mainTicket->id,
                ]);
            }
        }

        // Create some closed tickets for variety
        $additionalUsers = User::all()->skip(5)->take(3);
        foreach ($additionalUsers as $user) {
            Ticket::create([
                'user_id' => $user->id,
                'title' => 'Bug Report - Payment Failed',
                'subject' => 'Payment processing failed',
                'message' => 'I attempted to make a payment but it failed with an error message. Please investigate.',
                'status' => Ticket::STATUS_CLOSED,
                'ticket_id' => null,
            ]);
        }
    }
}
