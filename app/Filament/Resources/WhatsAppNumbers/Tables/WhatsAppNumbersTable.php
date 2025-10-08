<?php

namespace App\Filament\Resources\WhatsAppNumbers\Tables;

use App\Models\WhatsAppNumber;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class WhatsAppNumbersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('mobile')
                    ->label(__('filament.resources.whatsapp_numbers.table.mobile'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),

                Columns\TextColumn::make('name')
                    ->label(__('filament.resources.whatsapp_numbers.table.name'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Columns\TextColumn::make('session_id')
                    ->label(__('filament.resources.whatsapp_numbers.table.session_id'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Columns\BadgeColumn::make('status')
                    ->label(__('filament.resources.whatsapp_numbers.table.status'))
                    ->colors([
                        'success' => 'connected',
                        'warning' => 'active',
                        'danger' => ['disconnected', 'error'],
                        'gray' => 'inactive',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'connected',
                        'heroicon-m-clock' => 'active',
                        'heroicon-m-x-circle' => ['disconnected', 'error'],
                        'heroicon-m-minus-circle' => 'inactive',
                    ])
                    ->sortable(),

                Columns\IconColumn::make('is_active')
                    ->label(__('filament.resources.whatsapp_numbers.table.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Columns\TextColumn::make('connected_at')
                    ->label(__('filament.resources.whatsapp_numbers.table.connected_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->placeholder(__('filament.resources.whatsapp_numbers.placeholders.never')),

                Columns\TextColumn::make('last_used_at')
                    ->label(__('filament.resources.whatsapp_numbers.table.last_used_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('filament.resources.whatsapp_numbers.placeholders.never')),

                Columns\TextColumn::make('usage_count')
                    ->label(__('filament.resources.whatsapp_numbers.table.usage_count'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Columns\TextColumn::make('error_count')
                    ->label(__('filament.resources.whatsapp_numbers.table.error_count'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn (WhatsAppNumber $record) => $record->error_count > 0 ? 'danger' : 'gray')
                    ->toggleable(),

                Columns\TextColumn::make('created_at')
                    ->label(__('filament.resources.whatsapp_numbers.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->label(__('filament.resources.whatsapp_numbers.filters.status'))
                    ->options([
                        'active' => __('filament.resources.whatsapp_numbers.options.status.active'),
                        'inactive' => __('filament.resources.whatsapp_numbers.options.status.inactive'),
                        'connected' => __('filament.resources.whatsapp_numbers.options.status.connected'),
                        'disconnected' => __('filament.resources.whatsapp_numbers.options.status.disconnected'),
                        'error' => __('filament.resources.whatsapp_numbers.options.status.error'),
                    ])
                    ->multiple(),

                Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.resources.whatsapp_numbers.filters.is_active'))
                    ->placeholder(__('filament.resources.whatsapp_numbers.filters.all_numbers'))
                    ->trueLabel(__('filament.resources.whatsapp_numbers.filters.only_active'))
                    ->falseLabel(__('filament.resources.whatsapp_numbers.filters.only_inactive')),

                Filters\Filter::make('never_used')
                    ->label(__('filament.resources.whatsapp_numbers.filters.never_used'))
                    ->query(fn ($query) => $query->where('usage_count', 0)),

                Filters\Filter::make('has_errors')
                    ->label(__('filament.resources.whatsapp_numbers.filters.has_errors'))
                    ->query(fn ($query) => $query->where('error_count', '>', 0)),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('test_connection')
                    ->label(__('filament.resources.whatsapp_numbers.actions.test_connection'))
                    ->icon('heroicon-o-wifi')
                    ->color('success')
                    ->action(function (WhatsAppNumber $record) {
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
                            $record->markAsDisconnected();

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
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->required()
                            ->placeholder('9123456789')
                            ->helperText('Enter your phone number to receive pairing code')
                            ->maxLength(10),
                    ])
                    ->modalHeading('Request WhatsApp Pairing Code')
                    ->modalDescription('Enter your phone number to receive an 8-digit pairing code via WhatsApp.')
                    ->action(function (WhatsAppNumber $record, array $data) {
                        $whatsappService = new WhatsAppService($record->session_id);

                        $result = $whatsappService->requestPairingCode($data['phone_number']);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Pairing Code Sent')
                                ->body('Pairing code has been sent to your WhatsApp. Use this code to authenticate the session.')
                                ->success()
                                ->send();

                            return $result;
                        } else {
                            Notification::make()
                                ->title('Failed to Send Pairing Code')
                                ->body('Failed to send pairing code: ' . ($result['error'] ?? 'Unknown error'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Generate a unique session ID if not provided
                        if (empty($data['session_id'])) {
                            $data['session_id'] = 'whatsapp_session_' . uniqid();
                        }
                        return $data;
                    }),

                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('filament.resources.whatsapp_numbers.actions.activate_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update([
                                'is_active' => true,
                                'status' => 'active'
                            ]);

                            Notification::make()
                                ->title('Numbers Activated')
                                ->body("{$records->count()} WhatsApp numbers have been activated.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label(__('filament.resources.whatsapp_numbers.actions.deactivate_selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each->update([
                                'is_active' => false,
                                'status' => 'inactive'
                            ]);

                            Notification::make()
                                ->title('Numbers Deactivated')
                                ->body("{$records->count()} WhatsApp numbers have been deactivated.")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('test_connections')
                        ->label(__('filament.resources.whatsapp_numbers.actions.test_connections'))
                        ->icon('heroicon-o-wifi')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $record) {
                                $whatsappService = new WhatsAppService($record->session_id);
                                $status = $whatsappService->getSessionStatus();

                                if ($status['success'] && $status['connected']) {
                                    $record->markAsConnected();
                                    $successCount++;
                                } else {
                                    $record->markAsDisconnected();
                                    $failCount++;
                                }
                            }

                            Notification::make()
                                ->title('Connection Test Completed')
                                ->body("{$successCount} successful, {$failCount} failed connections.")
                                ->info()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s'); // Auto-refresh every 30 seconds to show real-time status
    }
}
