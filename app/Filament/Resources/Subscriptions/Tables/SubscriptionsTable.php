<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('filament.resources.subscriptions.table.user_id'))
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label(__('filament.resources.subscriptions.table.plan_id'))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label(__('filament.resources.subscriptions.table.starts_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('filament.resources.subscriptions.table.ends_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('filament.resources.subscriptions.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'info',
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
