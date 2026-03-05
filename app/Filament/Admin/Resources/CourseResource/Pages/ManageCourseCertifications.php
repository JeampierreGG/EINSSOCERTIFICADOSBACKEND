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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cip'      => 'Colegio de Ingenieros',
                        'einsso'   => 'Einsso Consultores',
                        'megapack' => 'Mega Pack',
                        'solo_certificado' => 'Solo Certificado',
                        default    => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cip'              => 'danger',
                        'einsso'           => 'primary',
                        'megapack'         => 'success',
                        'solo_certificado' => 'info',
                        default            => 'gray',
                    }),
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
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Certificación')
                    ->modalHeading('Crear Opción de Certificación')
                    ->closeModalByClickingAway(false)
                    ->form($this->getCertificateForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['course_id'] = $this->record->id;
                        $data['megapack_items'] = $this->buildMegapackItems($data);
                        return $data;
                    })
                    ->extraAttributes(['style' => 'margin-right: auto;']),

                Tables\Actions\Action::make('manage_blocks')
                    ->label('Gestionar Bloques')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('warning')
                    ->url(fn () => CourseResource::getUrl('blocks', ['record' => $this->record]))
                    ->extraAttributes(['style' => 'margin-right: auto;']),

                Tables\Actions\Action::make('course_payments')
                    ->label('Pagos')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn () => CourseResource::getUrl('payments', ['record' => $this->record])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Opción de Certificación')
                    ->closeModalByClickingAway(false)
                    ->form($this->getCertificateForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['megapack_items'] = $this->buildMegapackItems($data);
                        return $data;
                    })
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Re-hydrate the structured fields from stored megapack_items
                        if (($data['type'] ?? '') === 'megapack' && !empty($data['megapack_items'])) {
                            foreach ($data['megapack_items'] as $item) {
                                $type = strtolower($item['certificate_type'] ?? '');
                                if (str_contains($type, 'einsso') || str_contains($type, 'consultores')) {
                                    $data['mp_einsso_qty']   = $item['quantity'] ?? 1;
                                    $data['mp_einsso_hours'] = $item['hours'] ?? '';
                                } elseif (str_contains($type, 'ingenieros') || str_contains($type, 'cip')) {
                                    $data['mp_cip_qty']   = $item['quantity'] ?? 1;
                                    $data['mp_cip_hours'] = $item['hours'] ?? '';
                                } elseif (str_contains($type, 'modular') || ($item['is_modular'] ?? 'no') === 'yes') {
                                    $data['mp_modular_hours']   = $item['hours'] ?? '';
                                    $data['mp_modular_modules'] = $item['modules'] ?? '';
                                }
                            }
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No hay opciones de certificación')
            ->emptyStateDescription('');
    }

    /**
     * Build the megapack_items array from the structured form fields.
     */
    protected function buildMegapackItems(array $data): ?array
    {
        if (($data['type'] ?? '') !== 'megapack') {
            return null;
        }

        $items = [];

        // Einsso Consultores item
        $einssoQty = intval($data['mp_einsso_qty'] ?? 0);
        if ($einssoQty > 0) {
            $items[] = [
                'certificate_type' => 'Einsso Consultores',
                'quantity'         => $einssoQty,
                'hours'            => $data['mp_einsso_hours'] ?? '',
                'is_modular'       => 'no',
                'modules'          => null,
            ];
        }

        // CIP item
        $cipQty = intval($data['mp_cip_qty'] ?? 0);
        if ($cipQty > 0) {
            $items[] = [
                'certificate_type' => 'Colegio de Ingenieros del Perú',
                'quantity'         => $cipQty,
                'hours'            => $data['mp_cip_hours'] ?? '',
                'is_modular'       => 'no',
                'modules'          => null,
            ];
        }

        // Modulares item
        $modularModules = trim($data['mp_modular_modules'] ?? '');
        if ($modularModules !== '') {
            $items[] = [
                'certificate_type' => 'Modulares',
                'quantity'         => 1,
                'hours'            => $data['mp_modular_hours'] ?? '',
                'is_modular'       => 'yes',
                'modules'          => $modularModules,
            ];
        }

        return !empty($items) ? $items : null;
    }

    protected function getCertificateForm(): array
    {
        // Default titles by type
        $defaultTitles = [
            'einsso'   => 'Certificado Einsso Consultores',
            'cip'      => 'Certificado CIP',
            'megapack' => 'Mega Pack',
        ];

        return [
            // ── Tipo de Certificación ───────────────────────────────────────
            Forms\Components\Radio::make('type')
                ->label('Tipo de Certificación')
                ->options(function () {
                    return \App\Models\Institution::orderByRaw("array_position(ARRAY['einsso','cip','megapack'], slug)")
                        ->get()
                        ->mapWithKeys(fn ($inst) => [
                            $inst->slug => $inst->name,
                        ])
                        ->toArray();
                })
                ->default('einsso')
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    // Auto-fill title
                    $titles = [
                        'einsso'   => 'Certificado Einsso Consultores',
                        'cip'      => 'Colegio de Ingenieros del Perú',
                        'megapack' => 'Mega Pack',
                    ];
                    $set('title', $titles[$state] ?? '');

                    // Auto-fill details
                    if ($state === 'cip') {
                        $set('details', "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General del COLEGIO DE INGENIEROS DEL PERÚ.\nTemario detallado y Promedio de calificación obtenida.");
                    } elseif ($state === 'einsso') {
                        $set('details', "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General de EINSSO CONSULTORES.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.");
                    } else {
                        // megapack
                        $set('details', "Certificado digital (Incluye código QR y código único de validación).\nFirma y Sello del Gerente General del COLEGIO DE INGENIEROS DEL PERÚ.\nFirma y Sello del Coordinador Académico de EINSSO CONSULTORES.\nTemario detallado y Promedio de calificación obtenida.");
                        // Reset structured fields
                        $set('mp_einsso_qty', 0);
                        $set('mp_einsso_hours', '');
                        $set('mp_cip_qty', 1);
                        $set('mp_cip_hours', '');
                        $set('mp_modular_hours', '');
                        $set('mp_modular_modules', '');
                    }
                })
                ->required(),

            // ── Título + Precio ─────────────────────────────────────────────
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título del Certificado')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('price')
                        ->label('Precio')
                        ->numeric()
                        ->prefix('S/')
                        ->required(),
                ]),

            // ── Descuento ───────────────────────────────────────────────────
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

            // ── Horas académicas (solo para tipos simples) ──────────────────
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('academic_hours')
                        ->label('Horas académicas')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(fn ($get) => in_array($get('type'), ['cip', 'einsso', 'solo_certificado']))
                        ->visible(fn ($get) => in_array($get('type'), ['cip', 'einsso', 'solo_certificado'])),
                ])
                ->visible(fn ($get) => in_array($get('type'), ['cip', 'einsso', 'solo_certificado'])),

            // ── Imágenes ────────────────────────────────────────────────────
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

            // ── ¿Qué incluye? (solo Mega Pack) ─────────────────────────────
            Forms\Components\Section::make('¿Qué incluye?')
                ->description('Configure los certificados que incluye este Mega Pack.')
                ->schema([
                    // ── Colegio de Ingenieros del Perú ──────────────────────
                    Forms\Components\Fieldset::make('Colegio de Ingenieros del Perú')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('decrement_cip')
                                            ->label('−')
                                            ->color('gray')
                                            ->size('sm')
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $val = max(0, intval($get('mp_cip_qty')) - 1);
                                                $set('mp_cip_qty', $val);
                                            }),
                                        Forms\Components\Actions\Action::make('increment_cip')
                                            ->label('+')
                                            ->color('primary')
                                            ->size('sm')
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $set('mp_cip_qty', intval($get('mp_cip_qty')) + 1);
                                            }),
                                    ])->label('Cantidad'),

                                    Forms\Components\TextInput::make('mp_cip_qty')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->integer()
                                        ->default(1)
                                        ->minValue(0)
                                        ->live()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('mp_cip_hours')
                                        ->label('Horas académicas')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0)
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // ── Einsso Consultores ──────────────────────────────────
                    Forms\Components\Fieldset::make('Einsso Consultores')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('decrement_einsso')
                                            ->label('−')
                                            ->color('gray')
                                            ->size('sm')
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $val = max(0, intval($get('mp_einsso_qty')) - 1);
                                                $set('mp_einsso_qty', $val);
                                            }),
                                        Forms\Components\Actions\Action::make('increment_einsso')
                                            ->label('+')
                                            ->color('primary')
                                            ->size('sm')
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $set('mp_einsso_qty', intval($get('mp_einsso_qty')) + 1);
                                            }),
                                    ])->label('Cantidad'),

                                    Forms\Components\TextInput::make('mp_einsso_qty')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->integer()
                                        ->default(0)
                                        ->minValue(0)
                                        ->live()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('mp_einsso_hours')
                                        ->label('Horas académicas')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0)
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // ── Modulares ───────────────────────────────────────────
                    Forms\Components\Fieldset::make('Modulares')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Textarea::make('mp_modular_modules')
                                        ->label('Títulos de los Módulos')
                                        ->rows(3)
                                        ->helperText('Un módulo por línea. Dejar vacío si no aplica.')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('mp_modular_hours')
                                        ->label('Horas académicas')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0)
                                        ->columnSpan(1),
                                ]),
                        ]),
                ])
                ->visible(fn ($get) => $get('type') === 'megapack')
                ->collapsible(false),

            // ── Detalles del Certificado ────────────────────────────────────
            Forms\Components\Textarea::make('details')
                ->label('Detalles del Certificado')
                ->rows(5)
                ->required(),
        ];
    }
}
