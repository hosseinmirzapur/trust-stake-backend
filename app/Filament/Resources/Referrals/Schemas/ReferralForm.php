<?php

namespace App\Filament\Resources\Referrals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReferralForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->label(__('filament.resources.referrals.form.user_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('referral_code')
                    ->label(__('filament.resources.referrals.form.referral_code'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('referred_by')
                    ->label(__('filament.resources.referrals.form.referred_by'))
                    ->maxLength(255),
            ]);
    }
}
