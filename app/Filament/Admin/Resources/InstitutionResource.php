<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InstitutionResource\Pages;
use App\Filament\Admin\Resources\InstitutionResource\RelationManagers;
use App\Models\Institution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Instituciones';
    protected static ?string $navigationGroup = 'Gestión Académica';

    public static function getModelLabel(): string
    {
        return 'Institución';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Instituciones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo_path')
                    ->disk(config('filesystems.default'))
                    ->directory('institutions')
                    ->image()
                    ->acceptedFileTypes(['image/png','image/jpeg','image/jpg','image/webp'])
                    ->visibility('private')
                    ->maxSize(5120),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\ImageColumn::make('logo_path')
                    ->disk(config('filesystems.default'))
                    ->visibility('private')
                    ->square(),
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
            'index' => Pages\ListInstitutions::route('/'),
            'create' => Pages\CreateInstitution::route('/create'),
            'edit' => Pages\EditInstitution::route('/{record}/edit'),
        ];
    }    
}
