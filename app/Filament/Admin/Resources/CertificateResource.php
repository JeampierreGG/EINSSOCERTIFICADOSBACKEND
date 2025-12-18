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
                        Forms\Components\TextInput::make('student_name')
                            ->label('Nombres y Apellidos')
                            ->placeholder('Escribe nombres y apellidos')
                            ->reactive()
                            ->live()
                            ->required()
                            ->regex('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/u')
                            ->disabled(fn ($record) => filled($record))
                            ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                // En edición, mostrar el nombre del usuario vinculado
                                if (filled($state)) {
                                    return;
                                }
                                $id = $get('user_id');
                                if ($id) {
                                    $user = User::find($id);
                                    $set('student_name', $user?->name);
                                }
                            })
                            ->datalist(function (Forms\Get $get) {
                                $q = trim((string) $get('student_name'));
                                if ($q === '') {
                                    return User::query()
                                        ->where('is_admin', false)
                                        ->orderBy('name')
                                        ->limit(10)
                                        ->pluck('name')
                                        ->all();
                                }
                                return User::query()
                                    ->where('is_admin', false)
                                    ->whereRaw('LOWER(name) like ?', ['%' . strtolower($q) . '%'])
                                    ->orderBy('name')
                                    ->limit(20)
                                    ->pluck('name')
                                    ->all();
                            })
                            ->extraAttributes(['pattern' => '^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$', 'inputmode' => 'text'])
                            ->afterStateUpdated(function ($state, Set $set) {
                                $name = trim((string) $state);
                                // Sanitizar en vivo: eliminar cualquier número o símbolo, permitir solo letras y espacios
                                if ($name !== '') {
                                    $sanitized = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+/u', '', $name);
                                    if ($sanitized !== $name) {
                                        $set('student_name', $sanitized);
                                        // No continuar hasta que el estado se estabilice con caracteres válidos
                                        return;
                                    }
                                }
                                if ($name === '') {
                                    $set('user_id', null);
                                    $set('dni_ce', null);
                                    return;
                                }
                                $user = User::where('is_admin', false)->where('name', $name)->first();
                                if ($user) {
                                    $set('user_id', $user->id);
                                    $profile = UserProfile::where('user_id', $user->id)->first();
                                    $set('dni_ce', $profile?->dni_ce);
                                } else {
                                    $set('user_id', null);
                                }
                            }),
                        Forms\Components\TextInput::make('dni_ce')
                            ->label('DNI/CE')
                            ->maxLength(20)
                            ->dehydrated(fn ($record) => empty($record))
                            ->required()
                            ->numeric()
                            ->regex('/^[0-9]+$/')
                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record) {
                                $userId = $get('user_id') ?: ($record->user_id ?? null);
                                if ($userId) {
                                    $profile = UserProfile::where('user_id', $userId)->first();
                                    $set('dni_ce', $profile?->dni_ce);
                                }
                            })
                            ->disabled(function ($record, Get $get) {
                                return filled($record) || (filled($get('user_id')) && filled($get('dni_ce')));
                            }),
                        Forms\Components\Radio::make('type')
                            ->label('Tipo')
                            ->options([
                                'solo' => 'Solo certificado',
                                'megapack' => 'Megapack',
                            ])
                            ->inline()
                            ->required()
                            ->live()
                            ->disabled(fn ($record) => filled($record)),
                    ]),
                Forms\Components\Group::make()
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'solo')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('institution_id')
                                    ->label('Institución')
                                    ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('title')->label('Título del curso / módulo')->required()->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Radio::make('category')
                                    ->label('Categoría')
                                    ->options([
                                        'curso' => 'Curso',
                                        'modular' => 'Modular',
                                        'diplomado' => 'Diplomado',
                                    ])
                                    ->inline()
                                    ->required(),
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
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $v = is_numeric($state) ? (int) $state : 0;
                                        if ($v < 0) $v = 0;
                                        if ($v > 20) $v = 20;
                                        $set('grade', $v);
                                    })
                                    ->dehydrateStateUsing(fn ($state) => max(0, min(20, (int) $state)))
                                    ->extraAttributes(['inputmode' => 'numeric']),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('issue_date')->label('Fecha de emisión')->required()->native(true)->displayFormat('d/m/Y')->firstDayOfWeek(1),
                                Forms\Components\TextInput::make('code')
                                    ->label('Código de certificado')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->rules(['max:255'])
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
                                    ->required()
                                    ->maxSize(10240),
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
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('institution_id')
                                            ->label('Institución')
                                            ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\TextInput::make('title')->label('Título del curso / módulo')->required()->maxLength(255),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Radio::make('category')
                                            ->label('Categoría')
                                            ->options([
                                                'curso' => 'Curso',
                                                'modular' => 'Modular',
                                                'diplomado' => 'Diplomado',
                                            ])
                                            ->inline()
                                            ->required(),
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
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $v = is_numeric($state) ? (int) $state : 0;
                                                if ($v < 0) $v = 0;
                                                if ($v > 20) $v = 20;
                                                $set('grade', $v);
                                            })
                                            ->dehydrateStateUsing(fn ($state) => max(0, min(20, (int) $state)))
                                            ->extraAttributes(['inputmode' => 'numeric']),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('issue_date')->label('Fecha de emisión')->required()->native(true)->displayFormat('d/m/Y')->firstDayOfWeek(1),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Código de certificado')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->rules(['max:255','distinct'])
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
                                            ->required()
                                            ->maxSize(10240),
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
                Tables\Columns\TextColumn::make('type')->label('Tipo')->sortable(),
                Tables\Columns\TextColumn::make('institution.name')->label('Institución')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Título')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('issue_date')->date('d/m/Y')->label('Fecha de emisión'),
                Tables\Columns\TextColumn::make('code')->label('Código'),
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