<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AllyResource\Pages;
use App\Models\Ally;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AllyResource extends Resource
{
    protected static ?string $model = Ally::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Ajustes';
    protected static ?string $navigationLabel = 'Aliados';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'aliado';
    protected static ?string $pluralModelLabel = 'Aliados';
    protected static ?string $breadcrumb = 'Aliados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Logo del Aliado')
                    ->description('Sube únicamente el logo en formato PNG o SVG (fondo transparente). Dimensiones recomendadas: 300 × 150 px. Máximo 2 MB.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('allies')
                            ->visibility('public')
                            ->maxSize(2048) // 2 MB max
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'])
                            ->helperText('PNG, SVG, JPG o WebP (preferiblemente fondo transparente). Dimensiones recomendadas: 300 × 150 px.')
                            ->required()
                            ->columnSpanFull(),

                        // sort_order oculto: se asigna automáticamente al número más alto existente + 1
                        Forms\Components\Hidden::make('sort_order')
                            ->default(fn () => (Ally::max('sort_order') ?? -1) + 1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aliado activo')
                            ->helperText('Si está desactivado, no se mostrará en el sitio.')
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Usamos getState() con un closure para generar la URL correcta
                // igual que hace SystemSettingController, evitando el problema
                // de URLs corruptas de Filament con S3/Contabo.
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->getStateUsing(function (Ally $record): ?string {
                        $path = $record->logo_path;

                        if (!$path) return null;

                        // Filament a veces guarda la ruta como JSON array
                        if (is_string($path) && (str_starts_with($path, '[') || str_starts_with($path, '{'))) {
                            $decoded = json_decode($path, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $path = $decoded;
                            }
                        }

                        if (is_array($path)) {
                            $path = $path[0] ?? array_values($path)[0] ?? null;
                            if (is_array($path)) {
                                $path = array_values($path)[0] ?? null;
                            }
                        }

                        if (!$path || !is_string($path)) return null;

                        $path = ltrim(trim($path), '/');

                        try {
                            $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
                        } catch (\Throwable $e) {
                            try {
                                $url = Storage::disk('s3')->url($path);
                            } catch (\Throwable $e2) {
                                return null;
                            }
                        }

                        return str_replace('%3D', '=', $url);
                    })
                    ->height(50)
                    ->width(120)
                    ->extraImgAttributes(['style' => 'object-fit: contain; background: #f3f4f6; border-radius: 6px; padding: 4px;']),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Reordenar')
                    ->icon('heroicon-o-arrows-up-down'),
            )
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->emptyStateHeading('No hay aliados registrados')
            ->emptyStateDescription('Haz clic en "Nuevo Aliado" para agregar el primer logo.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Nuevo Aliado'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAllies::route('/'),
            'create' => Pages\CreateAlly::route('/create'),
            'edit'   => Pages\EditAlly::route('/{record}/edit'),
        ];
    }
}
