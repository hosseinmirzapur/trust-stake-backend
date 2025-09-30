<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('filament.resources.tickets.form.user_id'))
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('ticket_id')
                    ->label(__('filament.resources.tickets.form.parent_ticket'))
                    ->relationship('parent', 'subject') // Assuming 'parent' relationship and 'subject' as display
                    ->nullable(),
                TextInput::make('subject')
                    ->label(__('filament.resources.tickets.form.subject'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('message')
                    ->label(__('filament.resources.tickets.form.message'))
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label(__('filament.resources.tickets.form.image'))
                    ->image()
                    ->nullable(),
                Select::make('status')
                    ->label(__('filament.resources.tickets.form.status'))
                    ->options([
                        Ticket::STATUS_PENDING => __('filament.resources.tickets.form.status_pending'),
                        Ticket::STATUS_WAITING_FOR_RESPONSE => __('filament.resources.tickets.form.status_waiting_for_response'),
                        Ticket::STATUS_CLOSED => __('filament.resources.tickets.form.status_closed'),
                    ])
                    ->required()
                    ->native(false)
                    ->default(Ticket::STATUS_PENDING),
            ]);
    }
}
