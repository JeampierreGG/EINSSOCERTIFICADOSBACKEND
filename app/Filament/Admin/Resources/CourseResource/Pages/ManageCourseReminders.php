<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseReminderImage;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Computed;

class ManageCourseReminders extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-reminders';

    public Course $record;

    // Estado plano: claves = 'enrollment', 'opening', 'evaluation_X', 'evaluation_reminder_X'
    public array $images = [];

    public function mount(Course $record): void
    {
        $this->record = $record;
        $this->loadImages();
        $this->form->fill($this->images);
    }

    public function getTitle(): string
    {
        return 'Recordatorios: ' . $this->record->title;
    }

    // -----------------------------------------------------------------------
    // Carga de imágenes existentes desde BD
    // -----------------------------------------------------------------------

    protected function loadImages(): void
    {
        $reminders = CourseReminderImage::where('course_id', $this->record->id)->get();

        foreach ($reminders as $r) {
            if (!$r->image_path) continue;

            // Filament FileUpload espera el array en formato ['ruta' => 'ruta']
            // para poder mostrar la preview al cargar el formulario
            $entry = [$r->image_path => $r->image_path];

            if ($r->type === 'enrollment') {
                $this->images['enrollment'] = $entry;
            } elseif ($r->type === 'opening') {
                $this->images['opening'] = $entry;
            } elseif ($r->type === 'evaluation' && $r->evaluation_id) {
                $this->images['evaluation_' . $r->evaluation_id] = $entry;
            } elseif ($r->type === 'evaluation_reminder' && $r->evaluation_id) {
                $this->images['evaluation_reminder_' . $r->evaluation_id] = $entry;
            }
        }
    }

    // -----------------------------------------------------------------------
    // Evaluaciones del curso (Computed)
    // -----------------------------------------------------------------------

    #[Computed]
    public function evaluations()
    {
        return Evaluation::where('course_id', $this->record->id)->orderBy('id')->get();
    }

    // -----------------------------------------------------------------------
    // Formulario Filament unificado (compatible con Filament v3.0.0)
    // -----------------------------------------------------------------------

    public function form(Form $form): Form
    {
        $schema = [];

        // ---- Bloque 1: Confirmación de Matrícula ----
        $schema[] = Forms\Components\Section::make('Confirmación de Matrícula')
            ->description('Correo 1 — Se envía cuando el estudiante realiza la matrícula en el curso.')
            ->icon('heroicon-o-check-badge')
            ->schema([
                Forms\Components\FileUpload::make('enrollment')
                    ->label('Imagen del aula del curso')
                    ->disk('s3')
                    ->directory('course-reminders/' . $this->record->id)
                    ->visibility('public')
                    ->image()
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                    ->maxSize(5120)
                    ->helperText('📌 Tamaño sugerido: 800×450 px · Máx. 5 MB.')
                    ->columnSpanFull(),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('saveEnrollment')
                        ->label('Guardar imagen')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('success')
                        ->action('saveEnrollment'),
                ])->alignEnd()->columnSpanFull(),
            ]);

        // ---- Bloque 2: Apertura del Curso ----
        $schema[] = Forms\Components\Section::make('Apertura del Curso')
            ->description('Correo 2 — Se envía al inicio con datos del primer módulo y enlace de Zoom.')
            ->icon('heroicon-o-rocket-launch')
            ->schema([
                Forms\Components\FileUpload::make('opening')
                    ->label('Imagen del primer módulo con datos de Zoom')
                    ->disk('s3')
                    ->directory('course-reminders/' . $this->record->id)
                    ->visibility('public')
                    ->image()
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                    ->maxSize(5120)
                    ->helperText('📌 Captura del primer módulo con enlace Zoom, fecha y hora. Tamaño sugerido: 800×450 px · Máx. 5 MB.')
                    ->columnSpanFull(),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('saveOpening')
                        ->label('Guardar imagen')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('info')
                        ->action('saveOpening'),
                ])->alignEnd()->columnSpanFull(),
            ]);

        // ---- Bloques dinámicos por Evaluación ----
        foreach ($this->evaluations as $eval) {
            $evalId = $eval->id;

            // Correo 3: Evaluación
            $schema[] = Forms\Components\Section::make('Evaluación: ' . $eval->title)
                ->description('Correo 3 — Se envía cuando la evaluación está disponible en el aula virtual.')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Forms\Components\FileUpload::make('evaluation_' . $evalId)
                        ->label('Imagen de la evaluación')
                        ->disk('s3')
                        ->directory('course-reminders/' . $this->record->id)
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('170')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('📌 Captura de la evaluación disponible en el aula virtual. Máx. 5 MB.')
                        ->columnSpanFull(),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('saveEval_' . $evalId)
                            ->label('Guardar imagen')
                            ->icon('heroicon-o-cloud-arrow-up')
                            ->color('warning')
                            ->action(fn () => $this->saveEvaluationImage($evalId)),
                    ])->alignEnd()->columnSpanFull(),
                ]);

            // Correo 4: Recordatorio de Evaluación
            $schema[] = Forms\Components\Section::make('Recordatorio de Evaluación: ' . $eval->title)
                ->description('Correo 4 — Recordatorio enviado antes del cierre de la evaluación.')
                ->icon('heroicon-o-bell-alert')
                ->schema([
                    Forms\Components\FileUpload::make('evaluation_reminder_' . $evalId)
                        ->label('Imagen del recordatorio')
                        ->disk('s3')
                        ->directory('course-reminders/' . $this->record->id)
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('170')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('📌 Imagen de alerta con fecha límite de la evaluación. Máx. 5 MB.')
                        ->columnSpanFull(),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('saveEvalReminder_' . $evalId)
                            ->label('Guardar imagen')
                            ->icon('heroicon-o-cloud-arrow-up')
                            ->color('danger')
                            ->action(fn () => $this->saveEvaluationReminderImage($evalId)),
                    ])->alignEnd()->columnSpanFull(),
                ]);
        }

        return $form->schema($schema)->statePath('images');
    }

    // -----------------------------------------------------------------------
    // Acciones de guardado individuales
    // -----------------------------------------------------------------------

    public function saveEnrollment(): void
    {
        // getState() dispara el upload real a S3 (mueve archivo de Livewire temp al disco configurado)
        $state = $this->form->getState();
        $this->saveRecord('enrollment', null, (array) ($state['enrollment'] ?? []));
    }

    public function saveOpening(): void
    {
        $state = $this->form->getState();
        $this->saveRecord('opening', null, (array) ($state['opening'] ?? []));
    }

    public function saveEvaluationImage(int $evalId): void
    {
        $state = $this->form->getState();
        $this->saveRecord('evaluation', $evalId, (array) ($state['evaluation_' . $evalId] ?? []));
    }

    public function saveEvaluationReminderImage(int $evalId): void
    {
        $state = $this->form->getState();
        $this->saveRecord('evaluation_reminder', $evalId, (array) ($state['evaluation_reminder_' . $evalId] ?? []));
    }

    protected function saveRecord(string $type, ?int $evalId, array $imageList): void
    {
        // Filament devuelve el array con la ruta S3 como clave => clave, o como array simple.
        // Normalizamos: tomamos el primer valor del array.
        $imagePath = null;
        if (!empty($imageList)) {
            $first = array_values($imageList)[0];
            // Si es un array anidado (edge case), tomamos el primer elemento
            $imagePath = is_array($first) ? (array_values($first)[0] ?? null) : $first;
        }

        $query = CourseReminderImage::where('course_id', $this->record->id)->where('type', $type);
        $evalId !== null
            ? $query->where('evaluation_id', $evalId)
            : $query->whereNull('evaluation_id');

        $existing = $query->first();

        if ($existing) {
            $existing->update(['image_path' => $imagePath]);
        } else {
            CourseReminderImage::create([
                'course_id'     => $this->record->id,
                'type'          => $type,
                'evaluation_id' => $evalId,
                'image_path'    => $imagePath,
            ]);
        }

        Notification::make()
            ->title('Imagen guardada correctamente')
            ->success()
            ->send();
    }
}
