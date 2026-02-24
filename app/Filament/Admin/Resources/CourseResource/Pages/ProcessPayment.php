<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Certificate;
use App\Models\CertificateItem;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Closure;

class ProcessPayment extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.process-payment';

    public Payment $payment;
    public Course $course;

    public ?array $data = [];

    public function getTitle(): string 
    {
        return 'Generar Certificado';
    }

    public function mount(Course $record, Payment $payment): void
    {
        $this->course = $record;
        $this->payment = $payment;
        
        // Extract payment item details
        $items = is_string($this->payment->items) ? json_decode($this->payment->items, true) : $this->payment->items;
        
        // 1. Intentar identificar la Opción de Certificación específica
        $optionId = $items['id'] ?? null;
        $option = null;

        if ($optionId) {
            $option = \App\Models\CourseCertificateOption::find($optionId);
        }

        // Fallback: Buscar por título si no hay ID o no se encontró
        if (!$option && !empty($items['title']) && $this->payment->course_id) {
            $option = \App\Models\CourseCertificateOption::where('course_id', $this->payment->course_id)
                ->where('title', $items['title'])
                ->first();
        }

        // 2. Determinar el Tipo con normalización robusta y asignación de Institución explícita
        $cipId = Institution::where('slug', 'cip')->value('id');
        $einssoId = Institution::where('slug', 'einsso')->value('id');

        $detectedType = 'einsso';
        $institutionId = $einssoId;

        if ($option) {
            $rawType = strtolower($option->type ?? '');
            if ($rawType === 'megapack') {
                $detectedType = 'megapack';
                $institutionId = $cipId; // El principal del megapack es CIP
            } elseif ($rawType === 'cip') {
                $detectedType = 'cip';
                $institutionId = $cipId;
            } else {
                $detectedType = 'einsso';
                $institutionId = $einssoId;
            }
        } else {
            // Lógica Legacy antigua por título
            $itemTitle = $items['title'] ?? '';
            if (stripos($itemTitle, 'Mega Pack') !== false) {
                $detectedType = 'megapack';
                $institutionId = $cipId;
            } elseif (stripos($itemTitle, 'Colegio de Ingenieros') !== false || stripos($itemTitle, 'CIP') !== false) {
                $detectedType = 'cip';
                $institutionId = $cipId;
            }
        }

        $courseTitle = $this->payment->course?->title;
        $hours = $this->payment->course?->academic_hours ?? 0;
        $grade = $this->calculateGrade();
        
        // Items por defecto Megapack
        $defaultItems = [];
        if ($detectedType === 'megapack') {
            // 1. Principal (CIP)
            $defaultItems[] = [
                'is_main' => true,
                'institution_id' => $cipId,
                'custom_label' => 'Certificado CIP',
                'title' => $courseTitle,
                'hours' => $hours,
                'grade' => $grade,
                'issue_date' => null,
            ];
            // 2-5. Secundarios (Einsso)
            for ($i = 0; $i < 4; $i++) {
                $defaultItems[] = [
                    'is_main' => false,
                    'institution_id' => $einssoId,
                    'custom_label' => 'Certificado Modular ' . ($i + 1),
                    'grade' => 20,
                ];
            }
        }

        $this->form->fill([
            'certificate_option_id' => $option?->id,
            'type' => $detectedType,
            'title' => ($detectedType === 'megapack') ? null : $courseTitle,
            'user_id' => $this->payment->user_id,
            'institution_id' => $institutionId,
            'hours' => $hours, 
            'grade' => $grade,
            'issue_date' => null,
            'items' => $defaultItems,
        ]);
    }
    
    private function calculateGrade(): int
    {
        if (!$this->payment->course_id) return 0;

        $evaluations = \App\Models\Evaluation::where('course_id', $this->payment->course_id)->get();
        $totalEvaluations = $evaluations->count();
        if ($totalEvaluations === 0) return 0;

        $totalScore = 0;
        foreach ($evaluations as $eval) {
            $attempts = \App\Models\EvaluationAttempt::where('user_id', $this->payment->user_id)
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
        $items = is_string($this->payment->items) ? json_decode($this->payment->items, true) : $this->payment->items;
        $paidTitle = $items['title'] ?? 'N/A';
        $paidCourse = $items['course_title'] ?? 'N/A';

        return $form
            ->schema([
                Forms\Components\Section::make('Generar Certificado para: ' . $this->payment->user->name)
                    ->description(new \Illuminate\Support\HtmlString(
                        'DNI/CE: ' . ($this->payment->user->profile->dni_ce ?? 'S/D') . '<br>' .
                        'Item Pagado: <strong>' . $paidTitle . '</strong><br>' .
                        'Curso: <strong>' . $paidCourse . '</strong>'
                    ))
                    ->schema([
                        Forms\Components\Select::make('certificate_option_id')
                            ->label('Opción de Certificación')
                            ->options(function () {
                                if (!$this->payment->course_id) return [];
                                return \App\Models\CourseCertificateOption::where('course_id', $this->payment->course_id)
                                    ->pluck('title', 'id');
                            })
                            ->disabled()
                            ->dehydrated(false)
                            ->required(),

                        Forms\Components\Hidden::make('type')
                            ->live()
                            ->dehydrated(),
                        
                        // --- SECCIÓN SOLO CERTIFICADO (CIP / EINSSO) ---
                        Forms\Components\Group::make()
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['cip', 'einsso']))
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
                                        Forms\Components\TextInput::make('title')
                                            ->label('Título del curso / módulo')
                                            ->required()
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('hours')
                                            ->numeric()
                                            ->label('Horas')
                                            ->required()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('grade')
                                            ->label('Nota')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->default(0)
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
                                            ->disk(config('filesystems.default'))
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
                                Forms\Components\Repeater::make('items')
                                    ->label('Certificados del Pack')
                                    ->defaultItems(5)
                                    ->reorderable(false)
                                    ->itemLabel(fn (array $state): ?string => $state['custom_label'] ?? null)
                                    ->schema([
                                        Forms\Components\Hidden::make('custom_label'),
                                        Forms\Components\Hidden::make('is_main')->default(false),
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
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Título del curso / módulo')
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
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
                                                    ->default(0),
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
        $user = $this->payment->user;
        $profile = $user->profile;

        if (!$profile) {
            Notification::make()->title('Error')->body('El usuario no tiene perfil completo.')->danger()->send();
            return;
        }

        // Logic to create certificate (Identical to ProcessCertificate, but using $this->payment)
        // ... (Omitting full copy here for brevity, but I will include it in the real file write) ...
        // Actually, I need to include the full create method.
        
        $commonData = [
            'user_id' => $user->id,
            'nombres' => $profile->nombres,
            'apellidos' => $profile->apellidos,
            'dni_ce' => $profile->dni_ce,
            'type' => $data['type'],
        ];

        $inferCategory = function ($institutionId) {
            if (!$institutionId) return 'curso';
            $inst = \App\Models\Institution::find($institutionId);
            if (!$inst) return 'curso';
            return ($inst->slug === 'einsso') ? 'modular' : 'curso';
        };

        if (in_array($data['type'], ['cip', 'einsso'])) {
            $certData = array_merge($commonData, [
                'institution_id' => $data['institution_id'],
                'title' => $data['title'],
                'category' => $inferCategory($data['institution_id']),
                'hours' => $data['hours'],
                'grade' => $data['grade'],
                'issue_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $data['issue_date'])->format('Y-m-d'),
                'code' => strtoupper($data['code']),
                'file_path' => $data['file_path'] ?? null,
                'payment_id' => $this->payment->id,
            ]);
            $cert = Certificate::create($certData);
        } else {
            $cert = Certificate::create(array_merge($commonData, [
                'institution_id' => Institution::cip()?->id, 
                'title' => 'Megapack ' . count($data['items']) . ' Certificados',
                'category' => 'curso', 
                'hours' => 0,
                'grade' => 0,
                'issue_date' => now(),
                'code' => 'PACK-' . uniqid(), 
                'payment_id' => $this->payment->id,
            ]));

            foreach ($data['items'] as $item) {
                $cert->items()->create([
                    'institution_id' => $item['institution_id'],
                    'title' => $item['title'],
                    'category' => $inferCategory($item['institution_id']),
                    'hours' => $item['hours'] ,
                    'grade' => $item['grade'] ,
                    'issue_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $item['issue_date'])->format('Y-m-d'),
                    'code' => strtoupper($item['code']),
                    'file_path' => $item['file_path'] ?? null,
                ]);
            }
        }

        $recipientEmail = $this->payment->payer_email ?: $user->email;
        if ($recipientEmail && $cert) {
            try {
                if ($data['type'] === 'megapack') {
                    $cert->load('items');
                }
                \Illuminate\Support\Facades\Mail::to($recipientEmail)
                    ->send(new \App\Mail\CertificateSent($cert));
                Notification::make()->title('Correo enviado')->success()->send();
            } catch (\Exception $e) {
                Notification::make()->title('Error envío correo')->warning()->body($e->getMessage())->send();
            }
        }

        if ($cert) {
             $this->payment->update(['status' => 'finalized']);
        }

        Notification::make()->title('Certificado generado exitosamente')->success()->send();

        // Redirect to course payments using the NEW URL logic
        $this->redirect($this->getCancelUrl());
    }

    public function getCancelUrl(): string
    {
        return CourseResource::getUrl('payments', ['record' => $this->course->id]);
    }
}
