<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.resources.plans.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('filament.resources.plans.table.type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('filament.resources.plans.table.price'))
                    ->money('usd') // Assuming 'usd' as default currency
                    ->sortable(),
                TextColumn::make('profit')
                    ->label(__('filament.resources.plans.table.profit'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('lock_time')
                    ->label(__('filament.resources.plans.table.lock_time'))
                    ->date()
                    ->sortable(),
                IconColumn::make('disabled')
                    ->label(__('filament.resources.plans.table.disabled'))
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
