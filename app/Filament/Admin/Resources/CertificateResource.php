<?php
namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CertificateResource\Pages;
use App\Filament\Admin\Resources\CertificateResource\RelationManagers;
use App\Models\Certificate;
use App\Models\Institution;
use App\Models\CertificateItem;
use App\Models\User;
use App\Models\UserProfile;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Closure;
use Illuminate\Validation\Rule;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Certificados';
    protected static ?string $navigationGroup = 'Gestión Académica';

    public static function getModelLabel(): string
    {
        return 'Certificado';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Certificados';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Hidden::make('user_id'),
                        Forms\Components\TextInput::make('dni_ce')
                            ->label('DNI/CE')
                            ->maxLength(20)
                            ->required() // Requerido para buscar o crear
                            ->numeric() // Opcional si solo es numeros
                            ->lazy()
                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record) {
                                // Si estamos editando y hay usuario, cargar DNI
                                if (filled($state)) return;
                                
                                $userId = $get('user_id') ?: ($record->user_id ?? null);
                                if ($userId) {
                                    $profile = UserProfile::where('user_id', $userId)->first();
                                    $set('dni_ce', $profile?->dni_ce);
                                    $set('nombres', $profile?->nombres);
                                    $set('apellidos', $profile?->apellidos);
                                }
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Buscar usuario por DNI
                                if (!filled($state)) {
                                    $set('user_id', null);
                                    $set('nombres', null);
                                    $set('apellidos', null);
                                    return;
                                }

                                $profile = UserProfile::where('dni_ce', $state)->first();
                                if ($profile) {
                                    $set('user_id', $profile->user_id);
                                    $set('nombres', $profile->nombres);
                                    $set('apellidos', $profile->apellidos);
                                } else {
                                    // Nuevo DNI, limpiar ID para crear nuevo user
                                    $set('user_id', null);
                                    // Mantener nombres/apellidos si el usuario ya los escribió? Mejor no limpiar si no encontró
                                }
                            }),

                        Forms\Components\TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->lazy()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required()
                            ->lazy()
                            ->maxLength(255),
                        Forms\Components\Radio::make('type')
                            ->label('Tipo')
                            ->options([
                                'cip'      => 'Colegio de Ingenieros del Perú',
                                'einsso'   => 'Einsso Consultores',
                                'megapack' => 'Megapack',
                            ])
                            ->inline()
                            ->required()
                            ->live()
                            ->disabled(fn ($record) => filled($record))
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) return;

                                $cipId = Institution::where('slug', 'cip')->value('id');
                                $einssoId = Institution::where('slug', 'einsso')->value('id');

                                if ($state === 'cip') {
                                    $set('institution_id', $cipId);
                                    $set('items', []);
                                } elseif ($state === 'einsso') {
                                    $set('institution_id', $einssoId);
                                    $set('items', []);
                                } elseif ($state === 'megapack') {
                                    $set('institution_id', null);
                                    
                                    // Pre-fill 5 items
                                    $items = [];
                                    
                                    // 1. CIP
                                    $items[] = [
                                        'institution_id' => $cipId,
                                        'custom_label' => 'Certificado CIP',
                                    ];
                                    
                                    // 2-5. Einsso
                                    for ($i = 1; $i <= 4; $i++) {
                                        $items[] = [
                                            'institution_id' => $einssoId,
                                            'custom_label' => 'Certificado Modular ' . $i,
                                        ];
                                    }
                                    
                                    $set('items', $items);
                                }
                            }),
                    ]),
                Forms\Components\Group::make()
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['solo', 'cip', 'einsso']))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Institución')
                                    ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('title')->label('Título del curso / módulo')->required()->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('hours')
                                    ->numeric()
                                    ->label('Horas')
                                    ->required(),
                                Forms\Components\TextInput::make('grade')
                                    ->label('Nota')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(20)
                                    ->rules(['integer','between:0,20'])
                                    ->dehydrateStateUsing(fn ($state) => max(0, min(20, (int) $state)))
                                    ->extraAttributes(['inputmode' => 'numeric']),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('issue_date')
                                    ->label('Fecha de emisión')
                                    ->required()
                                    ->mask('99/99/9999')
                                    ->placeholder('DD/MM/AAAA')
                                    ->rules([
                                        'date_format:d/m/Y',
                                        'before_or_equal:today',
                                        function () {
                                            return function (string $attribute, $value, Closure $fail) {
                                                if (!$value) return;
                                                
                                                if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches)) {
                                                    $fail('El formato de fecha debe ser DD/MM/AAAA');
                                                    return;
                                                }
                                                
                                                $day = (int) $matches[1];
                                                $month = (int) $matches[2];
                                                $year = (int) $matches[3];
                                                
                                                if ($day < 1 || $day > 31) {
                                                    $fail('El día debe estar entre 01 y 31');
                                                    return;
                                                }
                                                
                                                if ($month < 1 || $month > 12) {
                                                    $fail('El mes debe estar entre 01 y 12');
                                                    return;
                                                }
                                                
                                                if ($year < 1900 || $year > date('Y')) {
                                                    $fail('El año debe ser válido');
                                                    return;
                                                }
                                                
                                                if (!checkdate($month, $day, $year)) {
                                                    $fail('La fecha ingresada no es válida');
                                                }
                                            };
                                        },
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromFormat('d/m/Y', $state)->format('Y-m-d') : null)
                                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : null),
                                Forms\Components\TextInput::make('code')
                                    ->label('Código de certificado')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->rules(['max:255'])
                                    ->extraAttributes([
    'style' => 'text-transform: uppercase;',
])
                                    ->dehydrateStateUsing(fn ($state) =>
                                                is_string($state) ? strtoupper(trim($state)) : $state
                                            )
                                            ->extraAttributes([
                                                'style' => 'text-transform: uppercase;',
                                    ])

                                    ->rule(function (?\App\Models\Certificate $record) {
                                        return function (string $attribute, $value, Closure $fail) use ($record) {
                                            $v = is_string($value) ? trim($value) : '';
                                            if ($v === '') return;
                                            $existsCert = Certificate::query()
                                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                                ->where('code', $v)
                                                ->exists();
                                            $existsItem = CertificateItem::query()
                                                ->where('code', $v)
                                                ->exists();
                                            if ($existsCert || $existsItem) {
                                                $fail('El código ya está en uso en otro certificado.');
                                            }
                                        };
                                    }),
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Archivo')
                                    ->disk(config('filesystems.default'))
                                    ->directory('certificates')
                                    ->acceptedFileTypes(['application/pdf','image/png','image/jpeg','image/jpg','image/webp'])
                                    ->visibility('private')
                                
                                    ->maxSize(10240)
                                    ->nullable() // <- Esto permite que Livewire no rompa al eliminar
                                    ->dehydrated(fn ($state) => !empty($state)) // Solo guarda si hay archivo
                                    ,
                            ]),
                    ]),
                Forms\Components\Group::make()
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'megapack')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('Certificados')
                            ->addActionLabel('Agregar certificado')
                            ->defaultItems(5)
                            ->collapsible()
                            ->grid(1)
                            ->itemLabel(fn (array $state): ?string => $state['custom_label'] ?? null)
                            ->schema([
                                Forms\Components\Hidden::make('custom_label'),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('institution_id')
                                            ->label('Institución')
                                            ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('title')->label('Título del curso / módulo')->required()->maxLength(255),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('hours')
                                            ->numeric()
                                            ->label('Horas')
                                            ->required(),
                                        Forms\Components\TextInput::make('grade')
                                            ->label('Nota')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->rules(['integer','between:0,20'])
                                            ->dehydrateStateUsing(fn ($state) => max(0, min(20, (int) $state)))
                                            ->extraAttributes(['inputmode' => 'numeric']),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('issue_date')
                                            ->label('Fecha de emisión')
                                            ->required()
                                            ->mask('99/99/9999')
                                            ->placeholder('DD/MM/AAAA')
                                            ->rules([
                                                'date_format:d/m/Y',
                                                'before_or_equal:today',
                                                function () {
                                                    return function (string $attribute, $value, Closure $fail) {
                                                        if (!$value) return;
                                                        
                                                        if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches)) {
                                                            $fail('El formato de fecha debe ser DD/MM/AAAA');
                                                            return;
                                                        }
                                                        
                                                        $day = (int) $matches[1];
                                                        $month = (int) $matches[2];
                                                        $year = (int) $matches[3];
                                                        
                                                        if ($day < 1 || $day > 31) {
                                                            $fail('El día debe estar entre 01 y 31');
                                                            return;
                                                        }
                                                        
                                                        if ($month < 1 || $month > 12) {
                                                            $fail('El mes debe estar entre 01 y 12');
                                                            return;
                                                        }
                                                        
                                                        if ($year < 1900 || $year > date('Y')) {
                                                            $fail('El año debe ser válido');
                                                            return;
                                                        }
                                                        
                                                        if (!checkdate($month, $day, $year)) {
                                                            $fail('La fecha ingresada no es válida');
                                                        }
                                                    };
                                                },
                                            ])
                                            ->dehydrateStateUsing(fn ($state) => $state ? \Carbon\Carbon::createFromFormat('d/m/Y', $state)->format('Y-m-d') : null)
                                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : null),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Código de certificado')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->rules(['max:255','distinct'])
                                            ->extraAttributes([
    'style' => 'text-transform: uppercase;',
])
                                            ->dehydrateStateUsing(fn ($state) =>
    is_string($state) ? strtoupper(trim($state)) : $state
)


                                            ->rule(function (?\App\Models\CertificateItem $record) {
                                                return function (string $attribute, $value, Closure $fail) use ($record) {
                                                    $v = is_string($value) ? trim($value) : '';
                                                    if ($v === '') return;
                                                    $existsItem = CertificateItem::query()
                                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                                        ->where('code', $v)
                                                        ->exists();
                                                    $existsCert = Certificate::query()
                                                        ->where('code', $v)
                                                        ->exists();
                                                    if ($existsItem || $existsCert) {
                                                        $fail('El código ya está en uso en otro certificado.');
                                                    }
                                                };
                                            }),
                                        Forms\Components\FileUpload::make('file_path')
                                            ->label('Archivo')
                                            ->disk(config('filesystems.default'))
                                            ->directory('certificates')
                                            ->acceptedFileTypes(['application/pdf','image/png','image/jpeg','image/jpg','image/webp'])
                                            ->visibility('private')
                                          ->reactive()
                                            ->maxSize(10240)
                                            ->nullable() // <- Esto permite que Livewire no rompa al eliminar
                                            ->dehydrated(fn ($state) => filled($state)) // Solo guarda si hay archivo
                                            ,
                                    ]),
                            ])
                            ->collapsible()
                            ->grid(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.profile.dni_ce')->label('DNI/CE')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cip'      => 'CIP',
                        'einsso'   => 'Einsso',
                        'megapack' => 'Megapack',
                        'solo'     => 'Solo',
                        default    => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cip'      => 'danger',
                        'einsso'   => 'primary',
                        'megapack' => 'success',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('institution.name')->label('Institución')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Título')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('issue_date')->date('d/m/Y')->label('Fecha de emisión'),
                Tables\Columns\TextColumn::make('code')->label('Código'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Filtrar por Curso')
                    ->options(fn () => \App\Models\Course::pluck('title', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('payment', fn (Builder $q) => $q->where('course_id', $data['value']));
                        }
                    }),
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
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }    
}
