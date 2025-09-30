<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament.resources.users.form.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('filament.resources.users.form.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('mobile')
                    ->label(__('filament.resources.users.form.mobile'))
                    ->tel()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('country')
                    ->label(__('filament.resources.users.form.country'))
                    ->maxLength(255),
                Select::make('role')
                    ->label(__('filament.resources.users.form.role'))
                    ->options([
                        User::ROLE_USER => __('filament.resources.users.form.role_user'),
                        User::ROLE_ADMIN => __('filament.resources.users.form.role_admin'),
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('referral_code')
                    ->label(__('filament.resources.users.form.referral_code'))
                    ->maxLength(255)
                    ->readOnly()
                    ->helperText(__('filament.resources.users.form.referral_code_helper')),
                DateTimePicker::make('email_verified_at')
                    ->label(__('filament.resources.users.form.email_verified_at'))
                    ->native(false)
                    ->disabled(),
                DateTimePicker::make('mobile_verified_at')
                    ->label(__('filament.resources.users.form.mobile_verified_at'))
                    ->native(false)
                    ->disabled(),
                TextInput::make('password')
                    ->label(__('filament.resources.users.form.password'))
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                    ->dehydrated(fn (string $state): bool => filled($state))
                    ->rules([Password::default()])
                    ->hiddenOn('edit'),
            ]);
    }
}
