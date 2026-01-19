<?php

namespace App\Filament\Admin\Resources\PaymentCompletedResource\Pages;

use App\Filament\Admin\Resources\PaymentCompletedResource;
use App\Models\Certificate;
use App\Models\CertificateItem;
use App\Models\Institution;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Closure;

class ProcessCertificate extends Page
{
    protected static string $resource = PaymentCompletedResource::class;

    protected static string $view = 'filament.admin.resources.payment-completed-resource.pages.process-certificate';

    public Payment $record;

    public ?array $data = [];

    public function getTitle(): string 
    {
        return 'Generar Certificado';
    }

    public function getBreadcrumb(): string
    {
        return 'Generar Certificado';
    }

    public function mount(Payment $record): void
    {
        $this->record = $record;
        
        // Extract payment item details
        $items = is_string($this->record->items) ? json_decode($this->record->items, true) : $this->record->items;
        
        // Determinar si es megapack buscando "Mega Pack" en el título del item pagado O en el curso
        // Pero prioridad al item pagado (que es el nombre de la opción de certificación)
        $itemTitle = $items['title'] ?? '';
        $courseTitle = $this->record->course?->title ?? $items['course_title'] ?? null;
        
        $isMegapack = false;
        if (stripos($itemTitle, 'Mega Pack') !== false || ($courseTitle && stripos($courseTitle, 'Mega Pack') !== false)) {
             $isMegapack = true;
        }

        $hours = $this->record->course?->academic_hours ?? 0;
        $grade = $this->calculateGrade();
        
        // Si no es megapack, el título por defecto debe ser el del curso
        $formTitle = $isMegapack ? null : $courseTitle;

        $defaultItems = [];
        if ($isMegapack) {
            // Item 1: Principal (Curso)
            $defaultItems[] = [
                'is_main' => true,
                'institution_id' => null,
                'title' => $courseTitle, // Título del curso real
                'category' => 'curso',
                'hours' => $hours,
                'grade' => $grade,
                'issue_date' => null,
                'code' => null,
                'file_path' => null
            ];
            // Items 2-4: Secundarios (Modulares)
            for ($i = 0; $i < 3; $i++) {
                $defaultItems[] = [
                    'is_main' => false,
                    'institution_id' => null,
                    'title' => null,
                    'category' => 'modular',
                    'hours' => null,
                    'grade' => 20,
                    'issue_date' => null,
                    'code' => null,
                    'file_path' => null
                ];
            }
        }

        $this->form->fill([
            'title' => $formTitle, 
            'type' => $isMegapack ? 'megapack' : 'solo', 
            'user_id' => $this->record->user_id,
            'hours' => $hours, 
            'grade' => $grade,
            'issue_date' => null,
            'items' => $defaultItems,
        ]);
    }
    
    private function calculateGrade(): int
    {
        if (!$this->record->course_id) return 0;

        // Get all evaluations for this course
        $evaluations = \App\Models\Evaluation::where('course_id', $this->record->course_id)->get();
        $totalEvaluations = $evaluations->count();
        
        if ($totalEvaluations === 0) return 0;

        $totalScore = 0;

        foreach ($evaluations as $eval) {
            // Get max score for this user on this evaluation, limiting to first 3 attempts
            $attempts = \App\Models\EvaluationAttempt::where('user_id', $this->record->user_id)
                ->where('evaluation_id', $eval->id)
                ->orderBy('id', 'asc')
                ->limit(3)
                ->get();
            
            if ($attempts->isNotEmpty()) {
                $totalScore += $attempts->max('score');
            }
        }

        return (int) round($totalScore / $totalEvaluations);
    }

    public function form(Form $form): Form
    {
        $items = is_string($this->record->items) ? json_decode($this->record->items, true) : $this->record->items;
        $paidTitle = $items['title'] ?? 'N/A';
        $paidCourse = $items['course_title'] ?? 'N/A';

        return $form
            ->schema([
                Forms\Components\Section::make('Generar Certificado para: ' . $this->record->user->name)
                    ->description(new \Illuminate\Support\HtmlString(
                        'DNI/CE: ' . ($this->record->user->profile->dni_ce ?? 'S/D') . '<br>' .
                        'Item Pagado: <strong>' . $paidTitle . '</strong><br>' .
                        'Curso: <strong>' . $paidCourse . '</strong>'
                    ))
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Tipo de Certificación')
                            ->options([
                                'solo' => 'Solo certificado',
                                'megapack' => 'Megapack',
                            ])
                            ->inline()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state === 'megapack') {
                                    $items = [];
                                    // Item 1
                                    $items[] = [
                                        'is_main' => true,
                                        'title' => $this->record->course?->title,
                                        'category' => 'curso',
                                        'hours' => $this->record->course?->academic_hours ?? 0,
                                        'grade' => $this->calculateGrade(),
                                        'issue_date' => null,
                                    ];
                                    // Items 2-4
                                    for($i=0; $i<3; $i++) {
                                        $items[] = [
                                            'is_main' => false,
                                            'category' => 'modular',
                                            'grade' => 20
                                        ];
                                    }
                                    $set('items', $items);
                                } elseif ($state === 'solo') {
                                    $record = $this->record ?? null; // Access record via component instance if available, or capture in closure use()
                                    // Actually $this is available in closure if bound? Filament closures bind to component.
                                    // Let's use $get/Set. To access record we might need to capture it.
                                    // Better: use $this->record directly as this closure is inside the class method.
                                    // Wait, is it? Yes inside form().
                                    if ($this->record) {
                                        $items = is_string($this->record->items) ? json_decode($this->record->items, true) : $this->record->items;
                                        // Prioritize course title directly from relationship
                                        $title = $this->record->course?->title ?? $items['title'] ?? $items['course_title'];
                                        $set('title', $title);
                                        $set('hours', $this->record->course?->academic_hours ?? 0);
                                        $set('grade', $this->calculateGrade());
                                        // $set('items', []); // Optional: clear items or keep them hidden
                                    }
                                }
                            })
                            ->disabled()
                            ->dehydrated(),
                        
                        // --- SECCIÓN SOLO CERTIFICADO ---
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
                                        Forms\Components\TextInput::make('title')
                                            ->label('Título del curso / módulo')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(false)
                                            ->dehydrated(),
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
                                            ->label('Horas')
                                            ->required()
                                            ->disabled(false)
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('grade')
                                            ->label('Nota')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->rules(['integer','between:0,20'])
                                            ->default(0)
                                            // ->disabled() Removed to allow manual edit
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('issue_date')
                                            ->label('Fecha de emisión')
                                            ->required()
                                            ->mask('99/99/9999')
                                            ->placeholder('DD/MM/AAAA'),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Código de certificado')
                                            ->required()
                                            ->unique(Certificate::class, 'code', ignoreRecord: true)
                                            ->extraAttributes(['style' => 'text-transform: uppercase;'])
                                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state))),
                                        Forms\Components\FileUpload::make('file_path')
                                            ->label('Archivo PDF/Imagen')
                                            ->disk('s3') // Usar S3 como en otros lados? O default? El original usa default.
                                            // El usuario pidió S3 para vouchers, ¿certificados también?
                                            // El CertificateResource original usa 'default'. Voy a usar 'default' (probablemente s3 config global)
                                            // para respetar el original, pero el usuario pidió S3 para pagos.
                                            // Asumiré configuración por defecto del sistema.
                                            ->directory('certificates')
                                            ->acceptedFileTypes(['application/pdf','image/png','image/jpeg','image/jpg','image/webp'])
                                            ->visibility('private'),
                                    ]),
                            ]),

                        // --- SECCIÓN MEGAPACK ---
                        Forms\Components\Group::make()
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'megapack')
                            ->schema([
                                Forms\Components\Repeater::make('items') // Note: This maps to $data['items'] which we need to save manually
                                    ->label('Certificados del Pack')
                                    ->defaultItems(4)
                                    ->reorderable(false)
                                    ->schema([
                                        Forms\Components\Hidden::make('is_main')->default(false),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('institution_id')
                                                    ->label('Institución')
                                                    ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Título del curso / módulo')
                                                    ->maxLength(255),
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
                                                    ->default('modular')
                                                    ->required()
                                                    ->live(),
                                                Forms\Components\TextInput::make('hours')
                                                    ->numeric()
                                                    ->label('Horas')
                                                    ->disabled(fn (Forms\Get $get) => $get('is_main'))
                                                    ->dehydrated(),
                                                Forms\Components\TextInput::make('grade')
                                                    ->label('Nota')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(20)
                                                    ->rules(['integer','between:0,20'])
                                                    ->default(0)
                                                    ->visible(fn (Get $get) => $get('category') !== 'modular'),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('issue_date')
                                                    ->label('Fecha de emisión')
                                                    ->required()
                                                    ->mask('99/99/9999')
                                                    ->placeholder('DD/MM/AAAA'),
                                                Forms\Components\TextInput::make('code')
                                                    ->label('Código de certificado')
                                                    ->required()
                                                    ->extraAttributes(['style' => 'text-transform: uppercase;'])
                                                    ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state))),
                                                Forms\Components\FileUpload::make('file_path')
                                                    ->label('Archivo')
                                                    ->disk(config('filesystems.default'))
                                                    ->directory('certificates')
                                                    ->acceptedFileTypes(['application/pdf','image/png','image/jpeg','image/jpg','image/webp'])
                                                    ->visibility('private')
                                                    ->maxSize(10240)
                                                    ->nullable(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->grid(1),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $user = $this->record->user;
        $profile = $user->profile;

        if (!$profile) {
            Notification::make()->title('Error')->body('El usuario no tiene perfil completo.')->danger()->send();
            return;
        }

        // Preparar datos comunes
        $commonData = [
            'user_id' => $user->id,
            'nombres' => $profile->nombres,
            'apellidos' => $profile->apellidos,
            'dni_ce' => $profile->dni_ce,
            'type' => $data['type'],
        ];

        if ($data['type'] === 'solo') {
            // Crear Certificado Único
            $certData = array_merge($commonData, [
                'institution_id' => $data['institution_id'],
                'title' => $data['title'],
                'category' => $data['category'],
                'hours' => $data['hours'],
                'grade' => $data['grade'],
                'issue_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $data['issue_date'])->format('Y-m-d'),
                'code' => strtoupper($data['code']),
                'file_path' => $data['file_path'] ?? null,
                'payment_id' => $this->record->id,
            ]);

            Certificate::create($certData);
        
        } else {
            // Crear Megapack (Certificado Padre + Items)
            $cert = Certificate::create(array_merge($commonData, [
                'institution_id' => null, 
                'title' => 'Megapack ' . count($data['items']) . ' Certificados',
                'category' => 'modular', // Dummy
                'hours' => 0,
                'grade' => 0,
                'issue_date' => now(),
                'code' => 'PACK-' . uniqid(), 
                'payment_id' => $this->record->id,
            ]));

            foreach ($data['items'] as $item) {
                $cert->items()->create([
                    'institution_id' => $item['institution_id'],
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'hours' => $item['hours'] ,
                    'grade' => $item['grade'] ,
                    'issue_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $item['issue_date'])->format('Y-m-d'),
                    'code' => strtoupper($item['code']),
                    'file_path' => $item['file_path'] ?? null,
                ]);
            }
        }

        Notification::make()
            ->title('Certificado generado exitosamente')
            ->success()
            ->send();

        $this->redirect(PaymentCompletedResource::getUrl('index'));
    }
}
