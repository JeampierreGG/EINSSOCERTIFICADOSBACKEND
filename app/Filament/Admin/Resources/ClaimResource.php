<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClaimResource\Pages;
use App\Filament\Admin\Resources\ClaimResource\RelationManagers;
use App\Models\Claim;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static ?string $navigationLabel = 'Reclamos';
    protected static ?string $modelLabel = 'Reclamo';
    protected static ?string $pluralModelLabel = 'Reclamos';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Reclamo')
                    ->schema([
                        Forms\Components\TextInput::make('ticket_code')
                            ->label('Código de Ticket')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha de Registro')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Identificación del Consumidor')
                    ->schema([
                        Forms\Components\TextInput::make('tipo_documento')
                            ->disabled(),
                        Forms\Components\TextInput::make('numero_documento')
                            ->disabled(),
                        Forms\Components\TextInput::make('nombres')
                            ->disabled(),
                        Forms\Components\TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->formatStateUsing(fn ($record) => $record ? trim("{$record->apellido_paterno} {$record->apellido_materno}") : '')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('telefono')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->disabled(),
                        Forms\Components\Textarea::make('domicilio')
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Detalle del Bien y Reclamación')
                    ->schema([
                        Forms\Components\TextInput::make('tipo_bien')
                            ->label('Bien Contratado')
                            ->disabled(),
                        Forms\Components\TextInput::make('monto_reclamado')
                            ->prefix('S/')
                            ->disabled(),
                        Forms\Components\Textarea::make('descripcion_bien')
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\TextInput::make('tipo_reclamacion')
                            ->label('Tipo (Reclamo/Queja)')
                            ->disabled(),
                        Forms\Components\Textarea::make('detalle')
                            ->label('Detalle de los hechos')
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\Textarea::make('pedido')
                            ->label('Pedido del consumidor')
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Gestión del Reclamo (Admin)')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'en_proceso' => 'En Proceso',
                                'atendido' => 'Atendido',
                                'rechazado' => 'Rechazado',
                            ])
                            ->native(false)
                            ->required(),
                        Forms\Components\DateTimePicker::make('fecha_atencion')
                            ->label('Fecha de Atención'),
                        Forms\Components\Textarea::make('respuesta_admin')
                            ->label('Respuesta / Acciones Tomadas')
                            ->columnSpanFull()
                            ->rows(5),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_code')
                    ->label('Ticket')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->label('Consumidor')
                    ->formatStateUsing(fn ($record) => "{$record->nombres} {$record->apellido_paterno}")
                    ->searchable(['nombres', 'apellido_paterno', 'numero_documento']),
                Tables\Columns\TextColumn::make('tipo_reclamacion')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'reclamo' => 'danger',
                        'queja' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En Proceso',
                        'atendido' => 'Atendido',
                        'rechazado' => 'Rechazado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'gray',
                        'en_proceso' => 'info',
                        'atendido' => 'success',
                        'rechazado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_atencion')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En Proceso',
                        'atendido' => 'Atendido',
                        'rechazado' => 'Rechazado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_reclamacion')
                    ->options([
                        'reclamo' => 'Reclamo',
                        'queja' => 'Queja',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Responder'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListClaims::route('/'),
            // 'create' => Pages\CreateClaim::route('/create'),
            'edit' => Pages\EditClaim::route('/{record}/edit'),
        ];
    }    
}
