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

class CreateEvaluation extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.create-evaluation';

    public Course $record;
    public ?array $data = [];

    public function mount(Course $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Nueva Evaluación';
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
                                    ->default(2)
                                    ->required()
                                    ->minValue(1),
                                
                                Forms\Components\TextInput::make('time_limit')
                                    ->label('Tiempo Límite (minutos)')
                                    ->numeric()
                                    ->default(30)
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

    public function create(): void
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

        // 1. Create Evaluation
        $evaluation = Evaluation::create([
            'course_id' => $this->record->id,
            'title' => $data['title'],
            // 'instructions' => $data['instructions'], // Removed
            'attempts' => $data['attempts'],
            'time_limit' => $data['time_limit'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            // 'questions' no longer stored in JSON
        ]);

        // 2. Create Questions & Options
        foreach ($questions as $qIndex => $qData) {
            $question = $evaluation->questionsRelation()->create([
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

        Notification::make()->title('Evaluación creada exitosamente')->success()->send();

        $this->redirect(CourseResource::getUrl('modules', ['record' => $this->record]));
    }
}
