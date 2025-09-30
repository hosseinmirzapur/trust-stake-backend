<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Fieldset::make('Plan Details')
                    ->label(__('filament.resources.plans.form.plan_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament.resources.plans.form.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('type')
                            ->label(__('filament.resources.plans.form.type'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label(__('filament.resources.plans.form.price'))
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        TextInput::make('profit')
                            ->label(__('filament.resources.plans.form.profit'))
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                        DatePicker::make('lock_time')
                            ->label(__('filament.resources.plans.form.lock_time'))
                            ->required()
                            ->native(false),
                        Toggle::make('disabled')
                            ->label(__('filament.resources.plans.form.disabled'))
                            ->helperText(__('filament.resources.plans.form.disabled_helper'))
                            ->default(false),
                    ])
            ]);
    }
}
