<?php

namespace App\Filament\Resources\Wallets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WalletForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('filament.resources.wallets.form.user_id'))
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('balance')
                    ->label(__('filament.resources.wallets.form.balance'))
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->label(__('filament.resources.wallets.form.currency'))
                    ->required()
                    ->default('USDT')
                    ->maxLength(255),
            ]);
    }
}
