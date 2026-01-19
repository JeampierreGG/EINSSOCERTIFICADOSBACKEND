<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PendingPayments extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pagos Pendientes de Revisión';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()->where('status', 'pending')->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Estudiante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('certificationBlock.name')
                    ->label('Bloque')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Método'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('PEN'),
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Código Op.'),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Revisar')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->modalHeading(fn (Payment $record) => 
                        ($record->user->name ?? 'Usuario') . ' | ' . 
                        ($record->user->email ?? '-') . ' | ' . 
                        ($record->user->profile->dni_ce ?? 'S/D')
                    )
                    ->modalWidth('6xl')
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
                                    ])->columns(1),

                                    Forms\Components\Section::make('Comprobante (Voucher)')->schema([
                                        Forms\Components\Placeholder::make('proof_image_view')
                                            ->label('')
                                            ->content(function (Payment $record) {
                                                $url = Storage::disk('s3')->temporaryUrl($record->proof_image_path, now()->addMinutes(20));
                                                return new HtmlString('
                                                    <div class="flex justify-center p-2 bg-gray-50 rounded-lg border border-gray-200">
                                                        <img src="' . $url . '" alt="Voucher" style="max-height: 200px; width: auto;" class="object-contain rounded-md shadow-sm" />
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
                            ->send();
                    })
                    ->modalSubmitActionLabel('Aceptar Pago')
                    ->extraModalFooterActions(function (Payment $record) {
                        return [
                            Tables\Actions\Action::make('reject_payment')
                                ->label('Rechazar')
                                ->color('danger')
                                ->requiresConfirmation()
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
                    }),
            ]);
    }
}
