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
    protected static ?string $modelLabel = 'Estudiante';
    protected static ?string $pluralModelLabel = 'Estudiantes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role_id', 2)
            ->where('is_admin', false)
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
                Forms\Components\TextInput::make('name')
                    ->label('Nombres y Apellidos')
                    ->required()
                    ->regex('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/u'),
                Forms\Components\TextInput::make('dni_ce')
                    ->label('DNI/CE')
                    ->required()
                    ->numeric()
                    ->regex('/^[0-9]+$/')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Forms\Set $set, $record) {
                        $set('dni_ce', optional(optional($record)->profile)->dni_ce);
                    }),
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
