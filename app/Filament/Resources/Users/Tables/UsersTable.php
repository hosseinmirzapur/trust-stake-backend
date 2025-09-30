<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.resources.users.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('filament.resources.users.table.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile')
                    ->label(__('filament.resources.users.table.mobile'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->label(__('filament.resources.users.table.country'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label(__('filament.resources.users.table.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'user' => 'gray',
                        'admin' => 'success',
                        default => 'info',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('referral_code')
                    ->label(__('filament.resources.users.table.referral_code'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('email_verified_at')
                    ->label(__('filament.resources.users.table.email_verified_at'))
                    ->boolean()
                    ->sortable(),
                IconColumn::make('mobile_verified_at')
                    ->label(__('filament.resources.users.table.mobile_verified_at'))
                    ->boolean()
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
