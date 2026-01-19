<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseCertificateOption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ManageCourseCertifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-certifications';

    public Course $record;

    public function mount(Course $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Certificaciones: ' . $this->record->title;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CourseCertificateOption::query()->where('course_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'megapack' ? 'Mega Pack' : 'Solo Certificado')
                    ->color(fn ($state) => $state === 'megapack' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('PEN'),
                Tables\Columns\ImageColumn::make('image_1_path')
                    ->label('Img 1')
                    ->disk(config('filesystems.default'))
                    ->visibility('private'),
                Tables\Columns\ImageColumn::make('image_2_path')
                    ->label('Img 2')
                    ->disk(config('filesystems.default'))
                    ->visibility('private'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('manage_blocks')
                    ->label('Gestionar Bloques')
                    ->icon('heroicon-o-rectangle-stack')
                    ->url(fn () => CourseResource::getUrl('blocks', ['record' => $this->record])),
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Certificación')
                    ->modalHeading('Crear Opción de Certificación')
                    ->closeModalByClickingAway(false)
                    ->form($this->getCertificateForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['course_id'] = $this->record->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Opción de Certificación')
                    ->closeModalByClickingAway(false)
                    ->form($this->getCertificateForm()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No hay opciones de certificación')
            ->emptyStateDescription('');
    }

    protected function getCertificateForm(): array
    {
        return [
            Forms\Components\Radio::make('type')
                ->label('Tipo de Certificación')
                ->options([
                    'solo_certificado' => 'Solo Certificado',
                    'megapack' => 'Mega Pack',
                ])
                ->default('solo_certificado')
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state === 'solo_certificado') {
                        $set('title', '');
                        $set('details', "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General de EINSSO CONSULTORES.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.");
                    } else {
                        $set('title', 'Mega Pack');
                        $set('details', "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General del COLEGIO DE INGENIEROS DEL PERÚ.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.");
                        $set('megapack_items', [
                            ['certificate_type' => '', 'quantity' => 1, 'hours' => ''],
                            ['certificate_type' => '', 'quantity' => 1, 'hours' => ''],
                        ]);
                    }
                })
                ->required(),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título del Certificado')
                        ->required()
                        ->maxLength(255)
                        ->default(fn ($get) => $get('type') === 'megapack' ? 'Mega Pack' : ''),

                    Forms\Components\TextInput::make('price')
                        ->label('Precio')
                        ->numeric()
                        ->prefix('S/')
                        ->required(),
                ]),

            Forms\Components\Grid::make(2)
                ->schema([
                     Forms\Components\TextInput::make('discount_percentage')
                        ->label('Descuento %')
                        ->numeric()
                        ->suffix('%')
                        ->maxValue(100),
                    Forms\Components\DatePicker::make('discount_end_date')
                        ->label('Fin del Descuento'),
                ]),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('academic_hours')
                        ->label('Horas académicas')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(fn ($get) => $get('type') === 'solo_certificado')
                        ->visible(fn ($get) => $get('type') === 'solo_certificado'),
                ])
                ->visible(fn ($get) => $get('type') === 'solo_certificado'),

            Forms\Components\Select::make('certification_block_id')
                ->label('Bloque de Disponibilidad')
                ->options(function () {
                    return \App\Models\CertificationBlock::where('course_id', $this->record->id)
                        ->where('is_active', true)
                        ->orderBy('start_date', 'desc')
                        ->get()
                        ->mapWithKeys(function ($block) {
                            $dateRange = $block->start_date->format('d/m/Y') . ' - ' . $block->end_date->format('d/m/Y');
                            return [$block->id => $block->name . ' (' . $dateRange . ')'];
                        });
                })
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Seleccione el bloque/campaña durante el cual esta opción estará disponible para compra'),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\FileUpload::make('image_1_path')
                        ->label('Imagen Referencial 1')
                        ->disk(config('filesystems.default'))
                        ->directory('certificates')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->visibility('private')
                        ->maxSize(5120),
                    Forms\Components\FileUpload::make('image_2_path')
                        ->label('Imagen Referencial 2')
                        ->disk(config('filesystems.default'))
                        ->directory('certificates')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->visibility('private')
                        ->maxSize(5120),
                ]),

            Forms\Components\Repeater::make('megapack_items')
                ->label('Ítems del Mega Pack')
                ->visible(fn ($get) => $get('type') === 'megapack')
                ->schema([
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\TextInput::make('certificate_type')
                                ->label('Tipo de certificado')
                                ->columnSpan(2)
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->columnSpan(1)
                                ->required(),
                            Forms\Components\TextInput::make('hours')
                                ->label('Horas académicas')
                                ->numeric()
                                ->integer()
                                ->columnSpan(1)
                                ->required(),
                        ]),
                    
                    Forms\Components\Radio::make('is_modular')
                        ->label('¿Este ítem es Modular?')
                        ->options([
                            'no' => 'No',
                            'yes' => 'Sí',
                        ])
                        ->default('no')
                        ->live()
                        ->inline(),
                    
                    Forms\Components\Textarea::make('modules')
                        ->label('Títulos de los Módulos')
                        ->rows(3)
                        ->helperText('Ingrese los títulos de los módulos en un párrafo.')
                        ->visible(fn ($get) => $get('is_modular') === 'yes'),
                ])
                ->addActionLabel('Agregar más')
                ->defaultItems(0),

            Forms\Components\Textarea::make('details')
                ->label('Detalles del Certificado')
                ->rows(5)
                ->default(function ($get) {
                    if ($get('type') === 'megapack') {
                        return "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General del COLEGIO DE INGENIEROS DEL PERÚ.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.";
                    }
                    return "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General de EINSSO CONSULTORES.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.";
                })
                ->required(),
        ];
    }
}
