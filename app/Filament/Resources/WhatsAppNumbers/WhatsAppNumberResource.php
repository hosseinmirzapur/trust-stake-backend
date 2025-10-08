<?php

namespace App\Filament\Resources\WhatsAppNumbers;

use App\Filament\Resources\WhatsAppNumbers\Pages\CreateWhatsAppNumber;
use App\Filament\Resources\WhatsAppNumbers\Pages\EditWhatsAppNumber;
use App\Filament\Resources\WhatsAppNumbers\Pages\ListWhatsAppNumbers;
use App\Filament\Resources\WhatsAppNumbers\Schemas\WhatsAppNumberForm;
use App\Filament\Resources\WhatsAppNumbers\Tables\WhatsAppNumbersTable;
use App\Models\WhatsAppNumber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WhatsAppNumberResource extends Resource
{
    protected static ?string $model = WhatsAppNumber::class;

    protected static string|null|UnitEnum $navigationGroup = 'System Management';

    protected static ?string $navigationLabel = 'WhatsApp Numbers';

    protected static ?string $pluralModelLabel = 'WhatsApp Numbers';

    protected static ?string $modelLabel = 'WhatsApp Number';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    public static function getModelLabel(): string
    {
        return __('filament.resources.whatsapp_numbers.singular_label');
    }

    /**
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.whatsapp_numbers.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.whatsapp_numbers.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament.navigation.system_management');
    }

    public static function form(Schema $schema): Schema
    {
        return WhatsAppNumberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsAppNumbersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppNumbers::route('/'),
            'create' => CreateWhatsAppNumber::route('/create'),
            'edit' => EditWhatsAppNumber::route('/{record}/edit'),
        ];
    }
}
