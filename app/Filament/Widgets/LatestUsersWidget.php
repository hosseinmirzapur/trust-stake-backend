<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestUsersWidget extends BaseWidget
{
    protected static ?string $heading = 'جدیدترین کاربران';

    protected static ?int $sort = 2;

    protected function getTableQuery(): Builder
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
