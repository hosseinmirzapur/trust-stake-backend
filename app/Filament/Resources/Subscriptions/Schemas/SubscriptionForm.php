<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Subscription;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('filament.resources.subscriptions.form.user_id'))
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->label(__('filament.resources.subscriptions.form.plan_id'))
                    ->relationship('plan', 'name')
                    ->required(),
                DatePicker::make('starts_at')
                    ->label(__('filament.resources.subscriptions.form.starts_at'))
                    ->required(),
                DatePicker::make('ends_at')
                    ->label(__('filament.resources.subscriptions.form.ends_at'))
                    ->required(),
                Select::make('status')
                    ->label(__('filament.resources.subscriptions.form.status'))
                    ->options([
                        Subscription::STATUS_ACTIVE => __('filament.resources.subscriptions.form.status_active'),
                        Subscription::STATUS_INACTIVE => __('filament.resources.subscriptions.form.status_inactive'),
                    ])
                    ->required()
                    ->native(false)
                    ->default(Subscription::STATUS_ACTIVE),
            ]);
    }
}
