<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CourseResource\Pages;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Cursos';
    protected static ?string $navigationGroup = 'Gestión Académica';

    public static function getModelLabel(): string
    {
        return 'Curso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cursos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Información General')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Título del Curso')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('subtitle')
                                    ->label('Subtítulo del Curso')
                                    ->nullable(),

                                Forms\Components\TextInput::make('slug')
                                    ->hidden() // Hidden but auto-generated
                                    ->dehydrated()
                                    ->unique(Course::class, 'slug', ignoreRecord: true),

                                Forms\Components\TextInput::make('code')
                                    ->label('Código del Curso')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn (string $operation) => $operation === 'create'),


                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción Corta')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('objectives')
                                    ->label('Objetivos del curso')
                                    ->helperText('Describe los objetivos principales que se lograrán al completar el curso')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('target_audience')
                                    ->label('¿A quienes va dirigido?')
                                    ->helperText('Describe el público objetivo del curso')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('teacher_id')
                                    ->label('Instructor')
                                    ->options(function () {
                                        return \App\Models\Teacher::with('user')->get()->mapWithKeys(function ($teacher) {
                                            return [$teacher->id => $teacher->user->name]; // Show only name as requested
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('category')
                                    ->label('Categoría')
                                    ->datalist([
                                        'Gestión Integrada',
                                        'Seguridad y Salud',
                                        'Ingeniería y Proyectos',
                                        'Medio Ambiente',
                                        'Calidad',
                                        'Tecnología'
                                    ])
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Section::make('Detalles del Curso')
                            ->schema([
                                Forms\Components\Select::make('level')
                                    ->label('Nivel')
                                    ->options([
                                        'Básico' => 'Básico',
                                        'Intermedio' => 'Intermedio',
                                        'Avanzado' => 'Avanzado',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'draft' => 'Borrador',
                                        'published' => 'Publicado',
                                        'archived' => 'Archivado',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->native(false)
                                    ->helperText('Solo los cursos marcados como Publicado serán visibles en la web, dependiendo de sus fechas.'),
                                
                                Forms\Components\TextInput::make('sessions_count')
                                    ->label('Número de Sesiones')
                                    ->numeric()
                                    ->required(),
                                
                                Forms\Components\TextInput::make('academic_hours')
                                    ->label('Horas Académicas')
                                    ->numeric(),

                                Forms\Components\TextInput::make('duration_text')
                                    ->label('Duración (Semanas)')
                                    ->numeric()
                                    ->placeholder('Ej. 4'),
                                
                                Forms\Components\Select::make('class_type')
                                    ->label('Tipo de Clases')
                                    ->options([
                                        'sincrona' => 'Sincrónicas',
                                        'asincrona' => 'Asincrónicas',
                                    ])
                                    ->required()
                                    ->native(false),

                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->label('Grupo de WhatsApp')
                                    ->url(),
                            ])->columns(2),



                        Forms\Components\Section::make('Fechas y Precios')
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')->label('Fecha Inicio'),
                                Forms\Components\DatePicker::make('end_date')->label('Fecha Fin'),
                                
                                Forms\Components\Toggle::make('is_free')
                                    ->label('Es Gratuito')
                                    ->live(),

                                Forms\Components\TextInput::make('price')
                                    ->label('Precio Base')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->hidden(fn (Forms\Get $get) => $get('is_free')),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Multimedia')
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Imagen del card')
                                    ->disk(config('filesystems.default'))
                                    ->directory('courses')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                    ->visibility('private')
                                    ->maxSize(5120),

                                Forms\Components\FileUpload::make('welcome_image_path')
                                    ->label('Imagen de Bienvenida')
                                    ->disk(config('filesystems.default'))
                                    ->directory('courses/welcome')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                    ->visibility('private')
                                    ->maxSize(5120),

                                Forms\Components\FileUpload::make('brochure_path')
                                    ->label('Brochure (PDF)')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->disk(config('filesystems.default'))
                                    ->directory('brochures')
                                    ->visibility('private')
                                    ->maxSize(10240),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable()->limit(30),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('start_date')->date('d/m/Y')->label('Inicio'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'gray' => 'archived',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Borrador',
                        'archived' => 'Archivado',
                        'published' => 'Publicado',
                        default => $state
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->getStateUsing(fn ($record) => $record->is_free ? 'Gratis' : 'S/ ' . number_format($record->price ?? 0, 2)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'published' => 'Publicado',
                        'archived' => 'Archivado',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Gestión Integrada' => 'Gestión Integrada',
                        'Seguridad y Salud' => 'Seguridad y Salud',
                        'Ingeniería y Proyectos' => 'Ingeniería y Proyectos',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('modules')
                    ->label('Módulos')
                    ->icon('heroicon-o-book-open')
                    ->url(fn ($record) => static::getUrl('modules', ['record' => $record->id])),
                Tables\Actions\Action::make('certificates')
                    ->label('Certificación')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn ($record) => static::getUrl('certificates', ['record' => $record->id])),
                Tables\Actions\Action::make('enrollments')
                    ->label('Estudiantes')
                    ->icon('heroicon-o-users')
                    ->url(fn ($record) => static::getUrl('enrollments', ['record' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Removed from edit page - now accessed via action buttons in table
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
            'modules' => Pages\ManageCourseModules::route('/{record}/modules'),
            'certificates' => Pages\ManageCourseCertifications::route('/{record}/certificates'),
            'enrollments' => Pages\ManageCourseEnrollments::route('/{record}/enrollments'),
            'create-evaluation' => Pages\CreateEvaluation::route('/{record}/evaluations/create'),
            'edit-evaluation' => Pages\EditEvaluation::route('/{record}/evaluations/{evaluation}/edit'),
            'blocks' => Pages\ManageCourseBlocks::route('/{record}/blocks'),
            'block-payments' => Pages\ViewBlockPayments::route('/{record}/blocks/{blockId}/payments'),
        ];
    }
}
