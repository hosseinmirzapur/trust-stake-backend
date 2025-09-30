<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrustStakeStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(__('widgets.stats.total_users'), User::count())
                ->description(__('widgets.stats.total_users_description'))
                ->color('primary'),
            Stat::make(__('widgets.stats.total_subscriptions'), Subscription::count())
                ->description(__('widgets.stats.total_subscriptions_description'))
                ->color('success'),
            Stat::make(__('widgets.stats.total_transactions'), Transaction::count())
                ->description(__('widgets.stats.total_transactions_description'))
                ->color('info'),
            Stat::make(__('widgets.stats.admins'), User::where('role', User::ROLE_ADMIN)->count())
                ->description(__('widgets.stats.admins_description'))
                ->color('warning'),
        ];
    }
}
