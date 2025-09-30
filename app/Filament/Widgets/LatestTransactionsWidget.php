<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Transactions'; // This will be translated in the lang file

    protected static ?int $sort = 3;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Transaction::query()->latest()->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.name')
                ->label(__('widgets.latest_transactions.user')),
            TextColumn::make('amount')
                ->label(__('widgets.latest_transactions.amount'))
                ->money('usd'), // Assuming 'usd' as default currency
            TextColumn::make('type')
                ->label(__('widgets.latest_transactions.type'))
                ->badge(),
            TextColumn::make('status')
                ->label(__('widgets.latest_transactions.status'))
                ->badge(),
            TextColumn::make('created_at')
                ->label(__('widgets.latest_transactions.date'))
                ->dateTime(),
        ];
    }

    protected function getHeading(): ?string
    {
        return __('widgets.latest_transactions.heading');
    }
}
