<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InstitutionResource\Pages;
use App\Models\Institution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $slug = 'tipos-certificacion';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Tipos de Certificados';
    protected static ?string $navigationGroup = 'Gestión Académica';

    public static function getModelLabel(): string
    {
        return 'Tipo de Certificado';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tipos de Certificados';
    }

    public static function canCreate(): bool
    {
        return false; // No se pueden crear nuevos tipos
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // No se pueden eliminar tipos
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo')
                    ->disk(config('filesystems.default'))
                    ->directory('institutions')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                    ->visibility('private')
                    ->maxSize(5120)
                    ->disabled(fn ($record) => $record && !$record->isLogoEditable()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tipo de Certificado')
                    ->searchable()
                    ->sortable()
                    ->icon(fn (Institution $record) => match ($record->slug) {
                        'cip'      => 'heroicon-o-building-office',
                        'einsso'   => 'heroicon-o-academic-cap',
                        'megapack' => 'heroicon-o-gift',
                        default    => 'heroicon-o-document',
                    })
                    ->description(fn (Institution $record) => match ($record->slug) {
                        'cip', 'einsso' => $record->logo_path ? 'Logo configurado correctamente' : '⚠️ ATENCIÓN: Debe asignar el logo como primer paso',
                        'megapack'      => 'Usa logos de CIP y Einsso (Solo lectura)',
                        default         => '',
                    })
                    ->color(fn (Institution $record) => ($record->isLogoEditable() && !$record->logo_path) ? 'danger' : 'gray'),

                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logos')
                    ->disk(config('filesystems.default'))
                    ->visibility('private')
                    ->square()
                    ->size(60)
                    ->state(function (Institution $record) {
                        if ($record->slug === 'megapack') {
                            $cip = Institution::where('slug', 'cip')->first();
                            $einsso = Institution::where('slug', 'einsso')->first();
                            return array_filter([$cip?->logo_path, $einsso?->logo_path]);
                        }
                        return $record->logo_path;
                    })
                    ->limit(2)
                    ->limitedRemainingText(isSeparate: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('assign_logo')
                    ->label(fn (Institution $record) => $record->isLogoEditable() 
                        ? ($record->logo_path ? 'Cambiar Logo' : 'Asignar Logo') 
                        : 'Ver Logos')
                    ->icon('heroicon-o-photo')
                    ->color(fn (Institution $record) => $record->isLogoEditable() 
                        ? ($record->logo_path ? 'primary' : 'danger') 
                        : 'gray')
                    ->modalHeading(fn (Institution $record) => $record->isLogoEditable()
                        ? 'Asignar Logo - ' . $record->name
                        : 'Logos del Megapack')
                    ->modalWidth('md')
                    ->modalSubmitAction(fn (Institution $record) => $record->isLogoEditable() ? null : false)
                    ->form(function (Institution $record) {
                        if ($record->slug === 'megapack') {
                            $cip = Institution::where('slug', 'cip')->first();
                            $einsso = Institution::where('slug', 'einsso')->first();
                            $diskName = config('filesystems.default');
                            $storage = \Illuminate\Support\Facades\Storage::disk($diskName);

                            // Función helper para obtener la imagen en base64 (necesario si los archivos son 'private' y no accesibles por URL pública)
                            $getLogoSrc = function ($path) use ($storage) {
                                if (!$path || !$storage->exists($path)) return null;
                                try {
                                    $mime = $storage->mimeType($path);
                                    $content = base64_encode($storage->get($path));
                                    return "data:{$mime};base64,{$content}";
                                } catch (\Throwable $e) {
                                    return null;
                                }
                            };

                            $cipUrl = $getLogoSrc($cip?->logo_path);
                            $einssoUrl = $getLogoSrc($einsso?->logo_path);

                            return [
                                Forms\Components\Placeholder::make('cip_logo_display')
                                    ->label('Colegio de Ingenieros del Perú')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        $cipUrl 
                                            ? '<img src="' . $cipUrl . '" style="display: block; height: 100px; width: auto; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.25rem; background-color: #f9fafb;">'
                                            : '<span style="color: #ef4444; font-weight: 600;">⚠️ Sin logo asignado</span>'
                                    ))
                                    ->columnSpanFull(),
                                    
                                Forms\Components\Placeholder::make('einsso_logo_display')
                                    ->label('Certificado Modular (Einsso Consultores)')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        $einssoUrl 
                                            ? '<img src="' . $einssoUrl . '" style="display: block; height: 100px; width: auto; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.25rem; background-color: #f9fafb;">'
                                            : '<span style="color: #ef4444; font-weight: 600;">⚠️ Sin logo asignado</span>'
                                    ))
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            Forms\Components\FileUpload::make('logo_path')
                                ->label('Logo')
                                ->disk(config('filesystems.default'))
                                ->directory('institutions')
                                ->image()
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                ->visibility('private')
                                ->maxSize(5120)
                                ->disabled(!$record->isLogoEditable()),
                        ];
                    })
                    ->fillForm(fn (Institution $record) => [
                        'logo_path' => $record->logo_path,
                    ])
                    ->action(function (Institution $record, array $data) {
                        if (!$record->isLogoEditable()) {
                            return;
                        }

                        $record->update(['logo_path' => $data['logo_path']]);

                        Notification::make()
                            ->success()
                            ->title('Logo actualizado')
                            ->body('El logo de ' . $record->name . ' se actualizó correctamente.')
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->paginated(false)
            ->emptyStateHeading('No hay tipos de certificados configurados')
            ->emptyStateDescription('Ejecute el seeder para crear los tipos por defecto.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutions::route('/'),
        ];
    }
}
