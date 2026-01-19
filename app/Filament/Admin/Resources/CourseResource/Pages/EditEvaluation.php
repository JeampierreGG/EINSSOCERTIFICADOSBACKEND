<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Course;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class EditEvaluation extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.edit-evaluation'; // Reusing the view as it's likely just a form renderer

    public Course $record;
    public Evaluation $evaluation;
    public ?array $data = [];

    public function mount(Course $record, Evaluation $evaluation): void
    {
        $this->record = $record;
        $this->evaluation = $evaluation;

        // Verify that the evaluation belongs to the course
        if ($this->evaluation->course_id !== $this->record->id) {
            abort(404);
        }

        // Transform model relationships to array for the form Repeater
        $questions = $this->evaluation->questionsRelation->map(function ($q) {
            return [
                'question_text' => $q->question_text,
                'points' => $q->points,
                'options' => $q->options->map(function ($o) {
                    return [
                        'option_text' => $o->option_text,
                        'is_correct' => $o->is_correct,
                    ];
                })->toArray(),
            ];
        })->toArray();

        // Fallback for old JSON data if migration didn't happen but we want to edit it
        if (empty($questions) && !empty($this->evaluation->questions)) {
             $questions = $this->evaluation->questions;
             if (is_string($questions)) {
                 $questions = json_decode($questions, true) ?? [];
             }
        }

        $this->form->fill([
            'title' => $this->evaluation->title,
            // 'instructions' => $this->evaluation->instructions, // Removed
            'attempts' => $this->evaluation->attempts,
            'time_limit' => $this->evaluation->time_limit,
            'start_date' => $this->evaluation->start_date,
            'end_date' => $this->evaluation->end_date,
            'questions' => $questions,
        ]);
    }

    public function getTitle(): string
    {
        return 'Editar Evaluación: ' . $this->evaluation->title;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Evaluación')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título de la Evaluación')
                            ->required()
                            ->maxLength(255),
                        
                        
                        Forms\Components\Grid::make(1)
                            ->schema([
                                // Instructions removed
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('attempts')
                                    ->label('Número de Intentos')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                                
                                Forms\Components\TextInput::make('time_limit')
                                    ->label('Tiempo Límite (minutos)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Disponible desde'),
                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('Disponible hasta'),
                            ]),
                    ]),

                Forms\Components\Section::make('Preguntas')
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->label('Preguntas')
                            ->schema([
                                Forms\Components\Textarea::make('question_text')
                                    ->label('Enunciado de la Pregunta')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('points')
                                    ->label('Puntos')
                                    ->numeric()
                                    ->required()
                                    ->default(2)
                                    ->minValue(0)
                                    ->maxValue(20),

                                Forms\Components\Repeater::make('options')
                                    ->label('Opciones de Respuesta')
                                    ->schema([
                                        Forms\Components\TextInput::make('option_text')
                                            ->label('Texto de la Opción')
                                            ->required(),
                                        
                                        Forms\Components\Checkbox::make('is_correct')
                                            ->label('Correcta')
                                            ->helperText('Seleccione solo una correcta')
                                            ->default(false)
                                            ->live()
                                            ->disabled(fn (Forms\Get $get) => 
                                                !$get('is_correct') && 
                                                collect($get('../../options'))->pluck('is_correct')->contains(true)
                                            ),
                                    ])
                                    ->defaultItems(4)
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->grid(1),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? null)
                            ->addActionLabel('Agregar Pregunta')
                            ->defaultItems(1)
                            ->collapsible()
                            ->collapsed(false)
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Validate Questions Logic
        $questions = $data['questions'] ?? [];
        $totalPoints = 0;
        
        if (empty($questions)) {
             Notification::make()->title('Debe agregar al menos una pregunta.')->danger()->send();
             return;
        }

        foreach ($questions as $index => $q) {
            $points = intval($q['points'] ?? 0);
            $totalPoints += $points;

            $options = $q['options'] ?? [];
            if (count($options) < 2) {
                Notification::make()->title("La pregunta #" . ($index + 1) . " debe tener al menos 2 opciones.")->danger()->send();
                return;
            }

            $correctCount = collect($options)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                Notification::make()->title("La pregunta #" . ($index + 1) . " debe tener exactamente una opción marcada como correcta.")->danger()->send();
                return;
            }
        }

        if ($totalPoints > 20) {
            Notification::make()->title("El puntaje total es $totalPoints. No puede exceder de 20 puntos.")->danger()->send();
            return;
        }

        // 1. Update Evaluation
        $this->evaluation->update([
            'title' => $data['title'],
            // 'instructions' => $data['instructions'], // Removed
            'attempts' => $data['attempts'],
            'time_limit' => $data['time_limit'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            // 'questions' JSON no longer updated
        ]);

        // 2. Sync Questions (Simple approach: delete all and recreate. 
        // For more complex persistent IDs, we would need to track IDs in the repeater)
        // Since this is a simple exam editor, full replace is acceptable and safer for order/integrity.
        
        $this->evaluation->questionsRelation()->delete(); // Cascades options if set up in DB, or we trust Eloquent cascade on delete?
        // Note: We used cascadeOnDelete in migration, so DB handles cleaning children. 
        // But if soft deletes were used (not here), we'd need more care.
        
        foreach ($questions as $qIndex => $qData) {
            $question = $this->evaluation->questionsRelation()->create([
                'question_text' => $qData['question_text'],
                'points' => $qData['points'],
                'position' => $qIndex,
            ]);

            foreach (($qData['options'] ?? []) as $oIndex => $oData) {
                $question->options()->create([
                    'option_text' => $oData['option_text'],
                    'is_correct' => $oData['is_correct'] ?? false,
                    'position' => $oIndex,
                ]);
            }
        }

        Notification::make()->title('Evaluación actualizada exitosamente')->success()->send();

        $this->redirect(CourseResource::getUrl('modules', ['record' => $this->record]));
    }
}
