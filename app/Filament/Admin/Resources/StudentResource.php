<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Estudiantes';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestión Académica';
    protected static ?string $modelLabel = 'Estudiante';
    protected static ?string $pluralModelLabel = 'Estudiantes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role_id', 2)
            ->where('is_admin', false)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->withCount([
                'certificates as solo_certificates_count' => fn ($q) => $q->where('type', 'solo'),
                'certificateItems as megapack_items_count',
            ])
            ->with(['profile']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($state, Forms\Set $set, $record) => $set('nombres', $record?->profile?->nombres)),
                        
                        Forms\Components\TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($state, Forms\Set $set, $record) => $set('apellidos', $record?->profile?->apellidos)),

                        Forms\Components\TextInput::make('dni_ce')
                            ->label('DNI/CE')
                            ->required()
                            ->numeric()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($state, Forms\Set $set, $record) => $set('dni_ce', $record?->profile?->dni_ce)),

                        Forms\Components\Select::make('phone_code')
                            ->label('Cód. País')
                            ->options([
                                '+51' => 'Perú (+51)',
                                '+54' => 'Argentina (+54)',
                                '+591' => 'Bolivia (+591)',
                                '+55' => 'Brasil (+55)',
                                '+56' => 'Chile (+56)',
                                '+57' => 'Colombia (+57)',
                                '+593' => 'Ecuador (+593)',
                                '+52' => 'México (+52)',
                                '+34' => 'España (+34)',
                                '+1' => 'USA (+1)',
                                '+58' => 'Venezuela (+58)',
                            ])
                            ->searchable()
                            ->preload()
                            ->default('+51')
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $phone = $record?->profile?->phone;
                                if ($phone && preg_match('/\(\+(\d+)\)/', $phone, $matches)) {
                                    $component->state('+' . $matches[1]);
                                }
                            }),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Número Celular')
                            ->tel()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $phone = $record?->profile?->phone;
                                if ($phone) {
                                    $component->state(trim(preg_replace('/\(\+\d+\)/', '', $phone)));
                                }
                            }),

                        Forms\Components\TextInput::make('country')
                            ->label('País')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($state, Forms\Set $set, $record) => $set('country', $record?->profile?->country)),
                    ])->columns(2),

                Forms\Components\Section::make('Cuenta de Usuario')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required(),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombres y Apellidos')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('profile.dni_ce')->label('DNI/CE')->searchable(),
                Tables\Columns\TextColumn::make('total_certificates')
                    ->label('Certificados')
                    ->getStateUsing(fn (User $record) => (int)($record->solo_certificates_count ?? 0) + (int)($record->megapack_items_count ?? 0))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Filtrar por Curso')
                    ->options(fn () => \App\Models\Course::pluck('title', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('payments', fn (Builder $q) => $q->where('course_id', $data['value']));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => StudentResource\Pages\ListStudents::route('/'),
            'edit' => StudentResource\Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
