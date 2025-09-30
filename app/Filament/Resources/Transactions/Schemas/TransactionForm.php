<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Transaction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('wallet_id')
                    ->label(__('filament.resources.transactions.form.wallet_id'))
                    ->relationship('wallet', 'user.name') // Assuming wallet has a user relationship
                    ->required(),
                Select::make('type')
                    ->label(__('filament.resources.transactions.form.type'))
                    ->options([
                        Transaction::TYPE_DEPOSIT => __('filament.resources.transactions.form.type_deposit'),
                        Transaction::TYPE_WITHDRAW => __('filament.resources.transactions.form.type_withdraw'),
                        Transaction::TYPE_PAYMENT => __('filament.resources.transactions.form.type_payment'),
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('amount')
                    ->label(__('filament.resources.transactions.form.amount'))
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('balance_before')
                    ->label(__('filament.resources.transactions.form.balance_before'))
                    ->numeric()
                    ->readOnly()
                    ->prefix('$'),
                TextInput::make('balance_after')
                    ->label(__('filament.resources.transactions.form.balance_after'))
                    ->numeric()
                    ->readOnly()
                    ->prefix('$'),
                TextInput::make('tx_hash')
                    ->label(__('filament.resources.transactions.form.tx_hash'))
                    ->maxLength(255)
                    ->readOnly()
                    ->helperText(__('filament.resources.transactions.form.tx_hash_helper')),
                Select::make('status')
                    ->label(__('filament.resources.transactions.form.status'))
                    ->options([
                        Transaction::STATUS_PENDING => __('filament.resources.transactions.form.status_pending'),
                        Transaction::STATUS_COMPLETED => __('filament.resources.transactions.form.status_completed'),
                        Transaction::STATUS_FAILED => __('filament.resources.transactions.form.status_failed'),
                    ])
                    ->required()
                    ->native(false)
                    ->default(Transaction::STATUS_PENDING),
            ]);
    }
}
