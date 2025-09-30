<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wallet.user.name')
                    ->label(__('filament.resources.transactions.table.user_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('filament.resources.transactions.table.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Transaction::TYPE_DEPOSIT => 'success',
                        Transaction::TYPE_WITHDRAW => 'danger',
                        Transaction::TYPE_PAYMENT => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('filament.resources.transactions.table.amount'))
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('balance_before')
                    ->label(__('filament.resources.transactions.table.balance_before'))
                    ->money('usd')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance_after')
                    ->label(__('filament.resources.transactions.table.balance_after'))
                    ->money('usd')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tx_hash')
                    ->label(__('filament.resources.transactions.table.tx_hash'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('filament.resources.transactions.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Transaction::STATUS_COMPLETED => 'success',
                        Transaction::STATUS_PENDING => 'warning',
                        Transaction::STATUS_FAILED => 'danger',
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
