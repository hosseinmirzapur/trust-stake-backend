<?php

namespace App\Filament\Resources\WhatsAppNumbers\Schemas;

use App\Models\WhatsAppNumber;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppNumberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('filament.resources.whatsapp_numbers.sections.basic_information'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('mobile')
                            ->label(__('filament.resources.whatsapp_numbers.fields.mobile'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('9123456789')
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.mobile_help'))
                            ->maxLength(10)
                            ->validationMessages([
                                'unique' => 'This mobile number is already registered.',
                            ]),

                        Forms\Components\TextInput::make('session_id')
                            ->label(__('filament.resources.whatsapp_numbers.fields.session_id'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('whatsapp_session_123')
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.session_id_help'))
                            ->validationMessages([
                                'unique' => 'This session ID is already in use.',
                            ]),

                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.resources.whatsapp_numbers.fields.name'))
                            ->placeholder(__('filament.resources.whatsapp_numbers.placeholders.name'))
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.name_help'))
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.resources.whatsapp_numbers.fields.description'))
                            ->placeholder(__('filament.resources.whatsapp_numbers.placeholders.description'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament.resources.whatsapp_numbers.sections.status_configuration'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('filament.resources.whatsapp_numbers.fields.status'))
                            ->options([
                                'active' => __('filament.resources.whatsapp_numbers.options.status.active'),
                                'inactive' => __('filament.resources.whatsapp_numbers.options.status.inactive'),
                                'connected' => __('filament.resources.whatsapp_numbers.options.status.connected'),
                                'disconnected' => __('filament.resources.whatsapp_numbers.options.status.disconnected'),
                                'error' => __('filament.resources.whatsapp_numbers.options.status.error'),
                            ])
                            ->required()
                            ->default('inactive')
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.status_help')),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.resources.whatsapp_numbers.fields.is_active'))
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.is_active_help'))
                            ->default(true),

                        Forms\Components\DateTimePicker::make('connected_at')
                            ->label('Connected At')
                            ->helperText('When the session was successfully connected')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_used_at')
                            ->label('Last Used')
                            ->helperText('When this number was last used for OTP sending')
                            ->disabled(),
                    ]),

                Section::make(__('filament.resources.whatsapp_numbers.sections.statistics'))
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('usage_count')
                            ->label(__('filament.resources.whatsapp_numbers.fields.usage_count'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.usage_count_help')),

                        Forms\Components\TextInput::make('error_count')
                            ->label(__('filament.resources.whatsapp_numbers.fields.error_count'))
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.error_count_help')),

                        Forms\Components\KeyValue::make('settings')
                            ->label(__('filament.resources.whatsapp_numbers.fields.settings'))
                            ->keyLabel(__('filament.resources.whatsapp_numbers.fields.settings_key'))
                            ->valueLabel(__('filament.resources.whatsapp_numbers.fields.settings_value'))
                            ->helperText(__('filament.resources.whatsapp_numbers.fields.settings_help'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament.resources.whatsapp_numbers.sections.session_management'))
                    ->description(__('filament.resources.whatsapp_numbers.sections.session_management_description'))
                    ->schema([
                        Actions::make([
                            Action::make('test_connection')
                                ->label(__('filament.resources.whatsapp_numbers.actions.test_connection'))
                                ->icon('heroicon-o-wifi')
                                ->color('success')
                                ->action(function (WhatsAppNumber $record) {
                                    // Test WhatsApp session connection using the record's session ID
                                    $whatsappService = new WhatsAppService($record->session_id);
                                    $status = $whatsappService->getSessionStatus();

                                    if ($status['success'] && $status['connected']) {
                                        $record->markAsConnected();
                                        Notification::make()
                                            ->title('Connection Test Successful')
                                            ->body('WhatsApp session is connected and ready.')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Connection Test Failed')
                                            ->body('WhatsApp session is not connected. Please check the session status.')
                                            ->danger()
                                            ->send();
                                    }
                                }),

                            Action::make('restart_session')
                                ->label(__('filament.resources.whatsapp_numbers.actions.restart_session'))
                                ->icon('heroicon-o-arrow-path')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->action(function (WhatsAppNumber $record) {
                                    // Use the specific session ID from the record
                                    $whatsappService = new WhatsAppService($record->session_id);

                                    // First try to initialize the session (this will start it if it doesn't exist)
                                    $initResult = $whatsappService->initializeSession();

                                    if (!$initResult['success']) {
                                        Notification::make()
                                            ->title('Session Initialization Failed')
                                            ->body('Failed to initialize WhatsApp session: ' . ($initResult['error'] ?? 'Unknown error'))
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // If session needs authentication, inform the user
                                    if (isset($initResult['needs_auth']) && $initResult['needs_auth']) {
                                        Notification::make()
                                            ->title('Session Needs Authentication')
                                            ->body('WhatsApp session has been started and is waiting for authentication (QR code scanning).')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    // If session is already connected
                                    if (isset($initResult['connected']) && $initResult['connected']) {
                                        Notification::make()
                                            ->title('Session Already Connected')
                                            ->body('WhatsApp session is already connected and ready to use.')
                                            ->success()
                                            ->send();
                                        return;
                                    }

                                    // Try restart as fallback
                                    $result = $whatsappService->restartSession();

                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('Session Restarted')
                                            ->body('WhatsApp session has been restarted successfully.')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Restart Failed')
                                            ->body('Failed to restart WhatsApp session: ' . ($result['error'] ?? 'Unknown error'))
                                            ->danger()
                                            ->send();
                                    }
                                }),

                            Action::make('start_session')
                                ->label(__('filament.resources.whatsapp_numbers.actions.start_session'))
                                ->icon('heroicon-o-play')
                                ->color('info')
                                ->action(function (WhatsAppNumber $record) {
                                    $whatsappService = new WhatsAppService($record->session_id);

                                    // Start a new session
                                    $result = $whatsappService->startSession();

                                    if ($result['success']) {
                                        // Update the record status
                                        $record->update([
                                            'status' => 'active',
                                            'connected_at' => null,
                                            'error_count' => 0
                                        ]);

                                        Notification::make()
                                            ->title('Session Started')
                                            ->body('WhatsApp session has been started successfully. Use QR code authentication to connect.')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Failed to Start Session')
                                            ->body('Failed to start WhatsApp session: ' . ($result['error'] ?? 'Unknown error'))
                                            ->danger()
                                            ->send();
                                    }
                                }),

                            Action::make('show_qr_code')
                                ->label(__('filament.resources.whatsapp_numbers.actions.show_qr_code'))
                                ->icon('heroicon-o-qr-code')
                                ->color('gray')
                                ->modalHeading('WhatsApp QR Code Authentication')
                                ->modalDescription('Scan this QR code with WhatsApp on your phone to authenticate the session. Make sure WhatsApp is open and ready to scan.')
                                ->modalContent(function (WhatsAppNumber $record) {
                                    $whatsappService = new WhatsAppService($record->session_id);
                                    $qrResult = $whatsappService->getQRCode();

                                    if ($qrResult['success'] && isset($qrResult['image_data'])) {
                                        $imageData = $qrResult['image_data'];
                                        $contentType = $qrResult['content_type'] ?? 'image/png';

                                        return new \Illuminate\Support\HtmlString("
                                            <div class='text-center'>
                                                <div class='mb-4 p-4 bg-blue-50 rounded-lg'>
                                                    <p class='text-sm text-blue-800'>
                                                        📱 <strong>Ready to scan!</strong><br>
                                                        Open WhatsApp on your phone and scan this QR code to connect <strong>{$record->name}</strong>
                                                    </p>
                                                </div>
                                                <div class='flex justify-center mb-4'>
                                                    <img src='data:{$contentType};base64,{$imageData}' alt='WhatsApp QR Code' class='border-2 border-gray-300 rounded-lg max-w-xs shadow-lg' />
                                                </div>
                                                <div class='text-xs text-gray-500 space-y-1'>
                                                    <p><strong>Session ID:</strong> {$record->session_id}</p>
                                                    <p><strong>Mobile:</strong> {$record->mobile}</p>
                                                    <p class='text-amber-600'>⏰ QR code expires in 60 seconds</p>
                                                </div>
                                            </div>
                                        ");
                                    }

                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-center'>
                                            <div class='mb-4 p-4 bg-amber-50 rounded-lg'>
                                                <p class='text-sm text-amber-800'>
                                                    ⚠️ <strong>Session not ready</strong><br>
                                                    Please start the session first before viewing the QR code.
                                                </p>
                                            </div>
                                            <p class='text-gray-500'>
                                                Click 'Start New Session' first, then come back to scan the QR code.
                                            </p>
                                        </div>
                                    ");
                                })
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Close'),

                            Action::make('request_pairing_code')
                                ->label(__('filament.resources.whatsapp_numbers.actions.request_pairing_code'))
                                ->icon('heroicon-o-device-phone-mobile')
                                ->color('gray')
                                ->form([
                                    Forms\Components\TextInput::make('phone_number')
                                        ->label('Phone Number')
                                        ->required()
                                        ->placeholder('9123456789')
                                        ->helperText('Enter your phone number to receive pairing code')
                                        ->maxLength(10),
                                ])
                                ->modalHeading('Request WhatsApp Pairing Code')
                                ->modalDescription('Enter your phone number to receive a pairing code via WhatsApp.')
                                ->action(function (WhatsAppNumber $record, array $data) {
                                    $whatsappService = new WhatsAppService($record->session_id);

                                    $result = $whatsappService->requestPairingCode($data['phone_number']);

                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('Pairing Code Sent')
                                            ->body('Pairing code has been sent to your WhatsApp. Enter the 8-digit code below.')
                                            ->success()
                                            ->send();

                                        // You could return the pairing code here if needed
                                        return $result;
                                    } else {
                                        Notification::make()
                                            ->title('Failed to Send Pairing Code')
                                            ->body('Failed to send pairing code: ' . ($result['error'] ?? 'Unknown error'))
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull(),
                    ]),
            ]);
    }
}
