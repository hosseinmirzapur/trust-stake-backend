<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUsersWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Users'; // This will be translated in the lang file

    protected static ?int $sort = 2;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->latest()->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('widgets.latest_users.name')),
            TextColumn::make('email')
                ->label(__('widgets.latest_users.email')),
            TextColumn::make('created_at')
                ->label(__('widgets.latest_users.registered_at'))
                ->dateTime(),
        ];
    }

    protected function getHeading(): ?string
    {
        return __('widgets.latest_users.heading');
    }
}
