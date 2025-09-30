<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('filament.resources.tickets.table.user_id'))
                    ->searchable(),
                TextColumn::make('subject')
                    ->label(__('filament.resources.tickets.table.subject'))
                    ->searchable(),
                TextColumn::make('message')
                    ->label(__('filament.resources.tickets.table.message'))
                    ->searchable(),
                TextColumn::make('parent.subject')
                    ->label(__('filament.resources.tickets.table.parent_ticket'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('image')
                    ->label(__('filament.resources.tickets.table.image')),
                TextColumn::make('status')
                    ->label(__('filament.resources.tickets.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'waiting_for_response' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
