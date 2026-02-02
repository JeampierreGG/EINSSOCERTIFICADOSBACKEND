<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Admin\Resources\PaymentFinalizedResource\Pages;
use App\Filament\Admin\Resources\PaymentFinalizedResource\RelationManagers;
use App\Models\PaymentFinalized;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentFinalizedResource extends Resource
{
    protected static ?string $model = \App\Models\Payment::class;
    protected static ?string $modelLabel = 'Pago Completado';
    protected static ?string $pluralModelLabel = 'Pagos Completados';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?string $navigationLabel = 'Completados';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'payment-finalized';
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->has('certificate');
    }

    public static function form(Form $form): Form
    {
        return PaymentPendingResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->label('Usuario'),
                Tables\Columns\TextColumn::make('course.title')->label('Curso')->limit(30),
                Tables\Columns\TextColumn::make('certificate.code')->label('Cód. Certificado')->searchable(),
                Tables\Columns\TextColumn::make('certificate.issue_date')->date('d/m/Y')->label('Emisión'),
            ])
            ->filters([
                //
            ])
            ->actions([
                 Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (\App\Models\Payment $record) => static::getUrl('edit_certificate', ['record' => $record->id])),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentFinalizeds::route('/'),
            'edit_certificate' => Pages\EditProcessCertificate::route('/{record}/edit-certificate'),
        ];
    }    
}
