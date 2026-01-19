<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentMethodResource\Pages;
use App\Filament\Admin\Resources\PaymentMethodResource\RelationManagers;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?string $navigationLabel = 'Métodos de Pago';
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'método de pago';
    protected static ?string $pluralModelLabel = 'Métodos de Pago';
    protected static ?string $breadcrumb = 'Métodos de Pago';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Tipo - Mostramos primero los radio buttons
                Forms\Components\Radio::make('type')
                    ->label('Tipo')
                    ->options([
                        'yape' => 'Yape',
                        'bcp' => 'BCP',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        // Limpiamos campos específicos al cambiar de tipo
                        $set('qr_image_path', null);
                        $set('cci', null);
                        
                        // Establecemos las instrucciones por defecto según el tipo
                        if ($state === 'yape') {
                            $set('instructions', "Abre la app de Yape en tu celular.\nEscanea el código QR o ingresa el número de Yape.\nVerifica el nombre del titular del Yape antes de continuar.\nIngresa el monto a pagar.\nConfirma el pago y sube tu comprobante aquí.");
                        } elseif ($state === 'bcp') {
                            $set('instructions', "Abre la app Banca Móvil BCP.\nIngresa el número de cuenta o el CCI (si usas un banco distinto a BCP).\nVerifica el nombre del titular antes de continuar.\nIngresa el monto a pagar.\nConfirma el pago y sube tu comprobante aquí.");
                        }
                    })
                    ->inline()
                    ->columnSpanFull(),

                // Resto del formulario - solo visible después de seleccionar tipo
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('account_holder')
                        ->label('Titular de la cuenta')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Nombre del titular')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('account_number')
                        ->label(fn (Forms\Get $get) => match($get('type')) {
                            'yape' => 'Número de Yape',
                            'bcp' => 'Número de cuenta',
                            default => 'Número'
                        })
                        ->required()
                        ->maxLength(255)
                        ->placeholder(fn (Forms\Get $get) => match($get('type')) {
                            'yape' => 'Ej: 987654321',
                            'bcp' => 'Ej: 19400123456',
                            default => ''
                        })
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cci')
                        ->label('CCI (Código de Cuenta Interbancario)')
                        ->maxLength(255)
                        ->placeholder('Ej: 00219400012345678901')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'bcp'),

                    Forms\Components\FileUpload::make('qr_image_path')
                        ->label('Código QR de Yape')
                        ->image()
                        ->imageEditor()
                        ->disk(config('filesystems.default'))
                        ->directory('payment-qrs')
                        ->visibility('private')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                        ->helperText('Sube una imagen del código QR para pagos con Yape')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'yape'),

                    Forms\Components\Textarea::make('instructions')
                        ->label('Instrucciones')
                        ->rows(5)
                        ->placeholder('Instrucciones para el cliente')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Método activo')
                        ->helperText('Si está desactivado, no se mostrará como opción de pago')
                        ->default(true),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => filled($get('type')))
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'yape' => 'Yape',
                        'bcp' => 'BCP',
                        default => $state
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yape' => 'success',
                        'bcp' => 'warning',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('account_holder')
                    ->label('Titular')
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }    
}
