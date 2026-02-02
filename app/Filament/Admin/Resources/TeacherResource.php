<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeacherResource\Pages;
use App\Models\Teacher;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Docentes';
    protected static ?string $navigationGroup = 'Gestión Académica';

    public static function getModelLabel(): string
    {
        return 'Docente';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Docentes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Docente')
                    ->schema([
                        // Fila 1: Nombre Completo y Título
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user_name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record && $record->user) {
                                            $component->state($record->user->name);
                                        }
                                    }),

                                Forms\Components\TextInput::make('title')
                                    ->label('Profesión')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        // Fila 2: Sobre el Docente e Imagen
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // About field removed


                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Imagen')
                                    ->disk(config('filesystems.default'))
                                    ->directory('teachers')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                    ->visibility('private')
                                    ->maxSize(5120),
                            ]),

                        // Fila 3: Grado Académico y Experiencia Laboral
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('academic_degree')
                                    ->label('Grado Académico')
                                    ->placeholder('Ej. Magíster en Educación...')
                                    ->rows(3),

                                Forms\Components\Textarea::make('work_experience')
                                    ->label('Experiencia Laboral')
                                    ->rows(3),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Imagen')
                    ->disk(config('filesystems.default'))
                    ->visibility('private')
                    ->circular(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Profesión')
                    ->searchable(),
          
            ])
            ->filters([
                //
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
            ->emptyStateHeading('No hay docentes')
            ->emptyStateDescription(null)
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }    
}
