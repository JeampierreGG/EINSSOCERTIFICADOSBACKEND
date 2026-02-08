<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ManageCourseEnrollments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;
    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-enrollments';

    public Course $record;

    public function mount(Course $record): void
    {
        $this->record = $record;

        // Actualización automática de estado:
        // Si existe alguna evaluación VENCIDA (fecha fin < ahora) para la cual el estudiante
        // NO tiene ningún intento registrado, se considera inactivo.
        // REGLA: Se excluye la PRIMERA evaluación de esta regla.
        $firstEvalId = \App\Models\Evaluation::where('course_id', $record->id)
            ->orderBy('id', 'asc') // O por 'order' si existiera, usamos id como fallback de creación
            ->value('id');

        \App\Models\CourseEnrollment::where('course_id', $record->id)
            ->where('status', 'active')
            ->whereExists(function ($query) use ($firstEvalId) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('evaluations')
                    ->whereColumn('evaluations.course_id', 'course_enrollments.course_id')
                    ->where('evaluations.id', '!=', $firstEvalId) // EXCLUIR LA PRIMERA
                    ->where('evaluations.end_date', '<', now())
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('evaluation_attempts')
                            ->whereColumn('evaluation_attempts.evaluation_id', 'evaluations.id')
                            ->whereColumn('evaluation_attempts.user_id', 'course_enrollments.user_id');
                    });
            })
            ->update(['status' => 'inactive']);
    }

    public function getTitle(): string
    {
        return 'Estudiantes Inscritos: ' . $this->record->title;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CourseEnrollment::query()->where('course_id', $this->record->id))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.profile.dni_ce')
                    ->label('DNI/CE')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('Fecha de Inscripción')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ])
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Inscribir Estudiante')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('Inscribir Estudiante')
                    ->closeModalByClickingAway(false)
                    ->model(CourseEnrollment::class)
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Estudiante')
                            ->options(function () {
                                $courseId = $this->record->id;
                                $enrolledUserIds = \App\Models\CourseEnrollment::where('course_id', $courseId)->pluck('user_id');

                                return User::where('role_id', 2)
                                    ->whereNotNull('email')
                                    ->where('email', '!=', '')
                                    ->whereNotIn('id', $enrolledUserIds)
                                    ->with('profile')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        $label = $user->name;
                                        if ($user->profile && $user->profile->dni_ce) {
                                            $label .= ' - ' . $user->profile->dni_ce;
                                        }
                                        $label .= ' (' . $user->email . ')';
                                        return [$user->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->rules([
                                fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                    $courseId = $this->record->id;
                                    
                                    $exists = CourseEnrollment::where('course_id', $courseId)
                                        ->where('user_id', $value)
                                        ->exists();
                                    
                                    if ($exists) {
                                        $fail('Este estudiante ya está inscrito en el curso.');
                                    }
                                },
                            ]),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['course_id'] = $this->record->id;
                        $data['enrolled_at'] = now();
                        return $data;
                    })
                    ->successNotificationTitle('Estudiante inscrito exitosamente'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Estado')
                    ->modalHeading('Cambiar Estado')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->required()
                            ->native(false),
                    ]),
                Tables\Actions\Action::make('grades')
                    ->label('Notas')
                    ->icon('heroicon-o-academic-cap')
                    ->modalHeading('Gestión de Notas e Intentos')
                    ->modalWidth('5xl')
                    ->mountUsing(function (Forms\ComponentContainer $form, CourseEnrollment $record) {
                        $evaluations = \App\Models\Evaluation::where('course_id', $record->course_id)
                            ->orderBy('created_at', 'asc')
                            ->get();
                        $userId = $record->user_id;

                        $data = [];
                        foreach ($evaluations as $eval) {
                            // Find the max score first (Database aggregation is most reliable)
                            $maxScore = \App\Models\EvaluationAttempt::where('user_id', $userId)
                                ->where('evaluation_id', $eval->id)
                                ->max('score');

                            // Then retrieve the attempt record that corresponds to that score
                            $attempt = null;
                            if ($maxScore !== null) {
                                $attempt = \App\Models\EvaluationAttempt::where('user_id', $userId)
                                    ->where('evaluation_id', $eval->id)
                                    ->where('score', $maxScore)
                                    ->orderBy('created_at', 'desc') // In case of duplicates, grab latest
                                    ->first();
                            }

                            $attemptsUsed = \App\Models\EvaluationAttempt::where('user_id', $userId)
                                ->where('evaluation_id', $eval->id)
                                ->count();

                            $extension = \App\Models\EvaluationUserExtension::where('user_id', $userId)
                                ->where('evaluation_id', $eval->id)
                                ->first();

                            $data[] = [
                                'evaluation_id' => $eval->id,
                                'title' => $eval->title,
                                'score' => $attempt ? (float)$attempt->score : null,
                                'max_score_id' => $attempt ? $attempt->id : null,
                                'attempts_used' => $attemptsUsed,
                                'default_attempts' => $eval->attempts,
                                'extra_attempts' => $extension ? $extension->extra_attempts : 0,
                                'extended_end_date' => $extension ? $extension->extended_end_date : null,
                            ];
                        }
                        $form->fill(['evaluations' => $data]);
                    })
                    ->form([
                        Forms\Components\Repeater::make('evaluations')
                            ->label('Evaluaciones')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(7)
                            ->schema([
                                Forms\Components\Hidden::make('evaluation_id'),
                                Forms\Components\Hidden::make('max_score_id'),
                                Forms\Components\Hidden::make('default_attempts'),

                                Forms\Components\TextInput::make('title')
                                    ->label('Evaluación')
                                    ->disabled()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('score')
                                    ->label('Nota Máxima')
                                    ->numeric()
                                    ->step(0.01) // Allow decimals
                                    ->maxValue(20)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('extra_attempts')
                                    ->label('Intentos Extra')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live()
                                    ->columnSpan(1),
                                
                                Forms\Components\DateTimePicker::make('extended_end_date')
                                    ->label('Fecha Fin Individual')
                                    ->columnSpan(2),

                                Forms\Components\Placeholder::make('attempts_info')
                                    ->label('Uso / Total')
                                    ->content(fn (Forms\Get $get) => 
                                        $get('attempts_used') . ' / ' . ($get('default_attempts') + ((int)$get('extra_attempts')))
                                    )
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('attempts_used'),
                            ])
                    ])
                    ->action(function (array $data, CourseEnrollment $record) {
                        $userId = $record->user_id;
                        foreach ($data['evaluations'] as $item) {
                            // Update Score
                            if (isset($item['score']) && $item['score'] !== '' && $item['score'] !== null) {
                                if ($item['max_score_id']) {
                                    \App\Models\EvaluationAttempt::where('id', $item['max_score_id'])->update(['score' => $item['score']]);
                                } else {
                                    \App\Models\EvaluationAttempt::create([
                                        'user_id' => $userId,
                                        'evaluation_id' => $item['evaluation_id'],
                                        'course_id' => $record->course_id,
                                        'score' => $item['score'],
                                        'attempt_number' => 1,
                                        'completed_at' => now(),
                                    ]);
                                }
                            }

                            // Update Extension (Attempts & Date)
                            // Update Extension (Attempts & Date)
                            $keyExistsExtra = array_key_exists('extra_attempts', $item);
                            $keyExistsDate  = array_key_exists('extended_end_date', $item);

                            if ($keyExistsExtra || $keyExistsDate) {
                                $updateData = [];
                                
                                if ($keyExistsExtra) {
                                    $val = $item['extra_attempts'];
                                    // Si está vacío o null, forzar 0
                                    $updateData['extra_attempts'] = ($val === '' || $val === null) ? 0 : $val;
                                }
                                
                                if ($keyExistsDate) {
                                    $updateData['extended_end_date'] = $item['extended_end_date'];
                                }

                                \App\Models\EvaluationUserExtension::updateOrCreate(
                                    [
                                        'user_id' => $userId, 
                                        'evaluation_id' => $item['evaluation_id']
                                    ],
                                    $updateData
                                );
                            }
                        }
                        \Filament\Notifications\Notification::make()->success()->title('Registros actualizados')->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar Inscripción')
                    ->modalDescription('¿Está seguro/a de eliminar esta inscripción?')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('Cancelar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->modalHeading('Eliminar Inscripciones')
                        ->modalDescription('¿Está seguro/a de eliminar las inscripciones seleccionadas?')
                        ->modalSubmitActionLabel('Sí, eliminar')
                        ->modalCancelActionLabel('Cancelar'),
                ]),
            ]);
    }
}
