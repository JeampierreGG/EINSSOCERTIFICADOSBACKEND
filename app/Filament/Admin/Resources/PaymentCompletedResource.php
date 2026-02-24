<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentCompletedResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentCompletedResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $modelLabel = 'Pago Revisado';
    protected static ?string $pluralModelLabel = 'Pagos Revisados';
    
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?string $navigationLabel = 'Revisados';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'payment-reviewed';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', '!=', 'pending')
            ->doesntHave('certificate');
    }

    public static function form(Form $form): Form
    {
        // Reutilizamos el formulario de Pendientes
        return PaymentPendingResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->label('Usuario'),
                Tables\Columns\TextColumn::make('paymentMethod.name')->label('Método'),
                Tables\Columns\TextColumn::make('amount')->money('PEN')->label('Monto'),
                Tables\Columns\TextColumn::make('transaction_code')->label('Cód. Op.'),
                Tables\Columns\TextColumn::make('status')->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'pending' => 'Pendiente', // Should not appear here but good to have
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i', 'America/Lima')
                    ->label('Fecha Revisión'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('certification_block_id')
                    ->label('Bloque / Campaña')
                    ->relationship('certificationBlock', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersTriggerAction(
                fn (\Filament\Tables\Actions\Action $action) => $action
                    ->slideOver()
                    ->modalHeading('Filtro')
            )
            ->actions([
                Tables\Actions\Action::make('change_status')
                    ->label('Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                'approved' => 'Aprobado',
                                'rejected' => 'Rechazado',
                                'pending' => 'Pendiente',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->visible(fn (Payment $record) => $record->status === 'approved')
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'admin_note' => $data['admin_note'] ?? null,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Estado actualizado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('certificate')
                    ->label('Certificado')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->url(fn (Payment $record) => PaymentCompletedResource::getUrl('process-certificate', ['record' => $record]))
                    ->visible(fn (Payment $record) => $record->status === 'approved'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentCompleteds::route('/'),
            'process-certificate' => Pages\ProcessCertificate::route('/{record}/process-certificate'),
        ];
    }
}
