<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentPendingResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentPendingResource extends Resource
{
    protected static ?string $model = Payment::class; // Restaurado
    protected static ?string $modelLabel = 'Pago Pendiente';
    protected static ?string $pluralModelLabel = 'Pagos Pendientes';
    
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?string $navigationLabel = 'Pendientes';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'payment-pending';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'pending');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Detalles Financieros')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->label('Usuario')
                                ->disabled(),
                            
                            Forms\Components\Select::make('payment_method_id')
                                ->relationship('paymentMethod', 'name')
                                ->label('Método')
                                ->disabled(),
                            
                            Forms\Components\TextInput::make('amount')
                                ->label('Monto Pagado')
                                ->prefix('S/')
                                ->disabled(),

                            Forms\Components\Placeholder::make('discount_info')
                                ->label('Información de Descuento')
                                ->content(function (Payment $record) {
                                    $items = is_string($record->items) ? json_decode($record->items, true) : $record->items;
                                    $originalPrice = $items['original_price'] ?? null;
                                    $discountApplied = $items['discount_applied'] ?? 0;

                                    if ($discountApplied > 0) {
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="text-sm">
                                                <p><strong>Precio Original:</strong> S/ ' . number_format($originalPrice, 2) . '</p>
                                                <p><strong class="text-red-600">Descuento:</strong> ' . $discountApplied . '%</p>
                                            </div>
                                        ');
                                    }
                                    return 'Sin descuento aplicado.';
                                })
                                ->visible(function (Payment $record) {
                                    $items = is_string($record->items) ? json_decode($record->items, true) : $record->items;
                                    return ($items['discount_applied'] ?? 0) > 0;
                                }),
                            
                            Forms\Components\TextInput::make('transaction_code')
                                ->label('Código Operación')
                                ->disabled(),
                        ])->columns(2),
                    
                    Forms\Components\Section::make('Estado')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pendiente',
                                    'approved' => 'Aprobado',
                                    'rejected' => 'Rechazado',
                                ])
                                ->required(),
                        ]),
                ])->columnSpan(1),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Comprobante')
                        ->schema([
                            Forms\Components\FileUpload::make('proof_image_path')
                                ->image()
                                ->label('Voucher')
                                ->disabled() 
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(1),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->label('Usuario'),
                Tables\Columns\TextColumn::make('certificationBlock.name')->label('Bloque'),
                Tables\Columns\TextColumn::make('paymentMethod.name')->label('Método'),
                Tables\Columns\TextColumn::make('amount')->money('PEN')->label('Monto'),
                Tables\Columns\TextColumn::make('transaction_code')->label('Cód. Op.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s', 'America/Lima')
                    ->label('Fecha'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('certification_block_id')
                    ->label('Bloque / Campaña')
                    ->relationship('certificationBlock', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Detalles')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Payment $record) => 
                        ($record->user->name ?? 'Usuario') . ' | ' . 
                        ($record->user->email ?? '-') . ' | ' . 
                        ($record->user->profile->dni_ce ?? 'S/D')
                    )
                    ->modalWidth('6xl')
                    ->closeModalByClickingAway(false)
                    ->form(function (Payment $record) {
                        $items = is_string($record->items) ? json_decode($record->items, true) : $record->items;
                        $courseTitle = $items['course_title'] ?? 'N/A';
                        $certTitle = $items['title'] ?? 'N/A';
                        
                        return [
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Section::make('Información del Comprador')->schema([
                                        Forms\Components\TextInput::make('payer_first_name')
                                            ->label('Nombres')
                                            ->default($record->payer_first_name ?? '-')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('payer_last_name')
                                            ->label('Apellidos')
                                            ->default($record->payer_last_name ?? '-')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('payer_email')
                                            ->label('Correo para certificado')
                                            ->default($record->payer_email ?? '-')
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                    Forms\Components\Section::make('Detalles Financieros')->schema([
                                        Forms\Components\TextInput::make('method')
                                            ->label('Método')
                                            ->default($record->paymentMethod->name ?? 'N/A')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('amount_view')
                                            ->label('Monto Pagado')
                                            ->default('S/ ' . number_format($record->amount, 2))
                                            ->disabled(),
                                        
                                        Forms\Components\TextInput::make('original_price_view')
                                            ->label('Precio Original')
                                            ->default(isset($items['original_price']) ? 'S/ ' . number_format($items['original_price'], 2) : '-')
                                            ->visible(fn() => ($items['discount_applied'] ?? 0) > 0)
                                            ->disabled(),
                                        
                                        Forms\Components\TextInput::make('discount_view')
                                            ->label('Descuento Aplicado')
                                            ->default(($items['discount_applied'] ?? 0) . '%')
                                            ->visible(fn() => ($items['discount_applied'] ?? 0) > 0)
                                            ->disabled(),

                                        Forms\Components\TextInput::make('code')
                                            ->label('Cód. Operación / Fecha')
                                            ->default(($record->transaction_code ?? '-') . ' / ' . ($record->date_paid ? $record->date_paid->format('d/m/Y') : '-'))
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])->columns(2),
                                ])->columnSpan(1),

                                Forms\Components\Group::make([
                                    Forms\Components\Section::make('Lo que está pagando')->schema([
                                        Forms\Components\TextInput::make('course')
                                            ->label('Curso')
                                            ->default($courseTitle)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('cert')
                                            ->label('Opción Certificación')
                                            ->default($certTitle)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('block_view')
                                            ->label('Bloque / Campaña')
                                            ->default($record->certificationBlock->name ?? 'No especificado')
                                            ->disabled(),
                                    ])->columns(1),

                                    Forms\Components\Section::make('Comprobante (Voucher)')->schema([
                                        Forms\Components\Placeholder::make('proof_image_view')
                                            ->label('')
                                            ->content(function (Payment $record) {
                                                $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($record->proof_image_path, now()->addMinutes(20));
                                                return new \Illuminate\Support\HtmlString('
                                                    <div x-data="{ open: false }" 
                                                         @click="open = true"
                                                         class="flex justify-center p-2 bg-gray-50 rounded-lg border border-gray-200 relative group cursor-pointer hover:bg-gray-100 transition-colors">
                                                        <img src="' . $url . '" alt="Voucher" style="max-height: 150px; width: auto;" class="object-contain rounded-md shadow-sm" />
                                                        
                                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/5 rounded-lg">
                                                            <div class="p-2 bg-white rounded-full shadow-lg text-gray-700">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        <div x-show="open" 
                                                             style="display: none;" 
                                                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                                                             x-transition:enter="transition ease-out duration-300"
                                                             x-transition:enter-start="opacity-0"
                                                             x-transition:enter-end="opacity-100"
                                                             x-transition:leave="transition ease-in duration-200"
                                                             x-transition:leave-start="opacity-100"
                                                             x-transition:leave-end="opacity-0">
                                                             
                                                            <button @click.prevent.stop="open = false" type="button" class="absolute top-4 right-4 text-white hover:text-gray-300">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>

                                                            <img src="' . $url . '" style="max-height: 80vh; max-width: 90vw; width: auto; height: auto;" class="object-contain rounded shadow-2xl" @click.outside="open = false">
                                                        </div>
                                                    </div>
                                                ');
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                ])->columnSpan(1),
                            ]),
                        ];
                    })
                    ->action(function (Payment $record) {
                        $record->update(['status' => 'approved']);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Pago Aprobado')
                            ->body('El pago ha sido registrado y el certificado (si corresponde) debería procesarse.')
                            ->send();
                    })
                    ->modalSubmitActionLabel('Aceptar Pago')
                    ->extraModalFooterActions(function (Payment $record) {
                        return [
                            Tables\Actions\Action::make('reject_payment')
                                ->label('Rechazar')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Rechazar Pago')
                                ->modalDescription('Indique el motivo por el cual se rechaza este pago. El usuario será notificado (futuro).')
                                ->form([
                                    Forms\Components\Textarea::make('reason')
                                        ->label('Motivo del rechazo')
                                        ->required()
                                ])
                                ->action(function (array $data) use ($record) {
                                    $record->update([
                                        'status' => 'rejected', 
                                        'admin_note' => $data['reason']
                                    ]);
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Pago Rechazado')
                                        ->send();
                                })
                                ->cancelParentActions()
                        ];
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentPendings::route('/'),
        ];
    }
}
