<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ManageCourseModules extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-modules';

    public Course $record;

    public function mount(Course $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Módulos: ' . $this->record->title;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\CourseContent::query()->where('course_id', $this->record->id)->orderBy('order', 'asc'))
            ->columns([
                Tables\Columns\IconColumn::make('type')
                    ->label('')
                    ->icon(fn (string $state): string => match ($state) {
                        'module' => 'heroicon-o-book-open',
                        'evaluation' => 'heroicon-o-clipboard-document-check',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'module' => 'primary',
                        'evaluation' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Módulo')
                    ->modalHeading('Crear Módulo del Curso')
                    ->closeModalByClickingAway(false)
                    ->model(CourseModule::class)
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Título del Módulo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('order')
                                    ->label('Orden (Número del Módulo)')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn () => CourseModule::where('course_id', $this->record->id)->max('order') + 1)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('content')
                            ->label('Contenido del Temario')
                            ->helperText('Ingrese cada tema como una oración separada por puntos o saltos de línea.')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['course_id'] = $this->record->id;
                        $data['is_published'] = true;
                        $maxOrder = \App\Models\CourseContent::where('course_id', $this->record->id)->max('order');
                        $data['order'] = $maxOrder ? $maxOrder + 1 : 1;
                        return $data;
                    })
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        return $model::create($data);
                    })
                    ->successNotificationTitle('Módulo creado'),
                    
                Tables\Actions\Action::make('create_evaluation')
                    ->label('Nueva Evaluación')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn () => CourseResource::getUrl('create-evaluation', ['record' => $this->record])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->type !== 'module')
                    ->modalHeading('Editar Módulo del Curso')
                    ->record(fn ($record) => CourseModule::find($record->source_id))
                    ->fillForm(fn ($record) => CourseModule::find($record->source_id)->toArray())
                    ->closeModalByClickingAway(false)
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Título del Módulo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('order')
                                    ->label('Orden (Número del Módulo)')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('content')
                            ->label('Contenido del Temario')
                            ->helperText('Ingrese cada tema como una oración separada por puntos o saltos de línea.')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->using(function (array $data, $record): CourseModule {
                        $module = CourseModule::find($record->source_id);
                        $module->update($data);
                        return $module;
                    }),

                Tables\Actions\Action::make('material')
                    ->label('Material')
                    ->hidden(fn ($record) => $record->type !== 'module')
                    ->modalHeading('Gestionar Material del Módulo')
                    ->closeModalByClickingAway(false)
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->fillForm(function ($record) {
                        $module = CourseModule::with('materials')->find($record->source_id);
                        return [
                            'pdf_path' => $module->pdf_path,
                            'enable_date' => $module->enable_date,
                            'materials_list' => $module->materials->map(fn($m) => [
                                'file_path' => $m->file_path,
                            ])->toArray(),
                        ];
                    })
                    ->form([
                        Forms\Components\DatePicker::make('enable_date')
                            ->label('Fecha de Habilitación')
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->helperText('El material solo se mostrará después de esta fecha'),
                            
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('Material Principal (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk(config('filesystems.default'))
                            ->directory('course-materials')
                            ->visibility('private'),
                        
                        Forms\Components\Section::make('Material Adicional')
                            ->schema([
                                Forms\Components\Repeater::make('materials_list')
                                    ->label('Archivos Adicionales')
                                    ->schema([
                                        Forms\Components\FileUpload::make('file_path')
                                            ->label('Archivo')
                                            ->disk(config('filesystems.default'))
                                            ->directory('course-materials')
                                            ->visibility('private')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Agregar otro archivo')
                                    ->defaultItems(0)
                            ])
                            ->collapsible(),
                    ])
                    ->action(function ($record, array $data) {
                        $module = CourseModule::find($record->source_id);
                        $module->update([
                            'pdf_path' => $data['pdf_path'],
                            'enable_date' => $data['enable_date']
                        ]);

                        // Sync materials (Simple wipe and recreate approach)
                        $module->materials()->delete();
                        if (!empty($data['materials_list'])) {
                            foreach ($data['materials_list'] as $item) {
                                $module->materials()->create([
                                    'file_path' => $item['file_path'],
                                ]);
                            }
                        }
                    }),
                
                Tables\Actions\Action::make('zoom')
                    ->label('Zoom')
                    ->hidden(fn ($record) => $record->type !== 'module')
                    ->modalHeading('Configurar Enlace de Zoom')
                    ->closeModalByClickingAway(false)
                    ->icon('heroicon-o-video-camera')
                    ->color('info')
                    ->fillForm(fn ($record) => CourseModule::find($record->source_id)->only(['zoom_url', 'class_time']))
                    ->form([
                        Forms\Components\TextInput::make('zoom_url')
                            ->label('Enlace de Zoom')
                            ->url()
                            ->prefixIcon('heroicon-o-video-camera'),
                        Forms\Components\TimePicker::make('class_time')
                            ->label('Hora de Clase')
                            ->seconds(false),
                    ])
                    ->action(function ($record, array $data) {
                        CourseModule::find($record->source_id)->update($data);
                    }),
                
                Tables\Actions\Action::make('youtube')
                    ->label('YouTube')
                    ->hidden(fn ($record) => $record->type !== 'module')
                    ->modalHeading('Configurar Enlace de YouTube')
                    ->closeModalByClickingAway(false)
                    ->icon('heroicon-o-play')
                    ->color('danger')
                    ->fillForm(fn ($record) => CourseModule::find($record->source_id)->only(['video_url']))
                    ->form([
                        Forms\Components\TextInput::make('video_url')
                            ->label('Enlace de YouTube')
                            ->url()
                            ->prefixIcon('heroicon-o-play'),
                    ])
                    ->action(function ($record, array $data) {
                        CourseModule::find($record->source_id)->update($data);
                    }),

                 Tables\Actions\Action::make('edit_eval')
                    ->icon('heroicon-o-pencil')
                    ->label('Editar')
                    ->hidden(fn ($record) => $record->type !== 'evaluation')
                    ->url(fn ($record) => CourseResource::getUrl('edit-evaluation', ['record' => $this->record, 'evaluation' => $record->source_id])),
                    
                 Tables\Actions\DeleteAction::make()
                    ->action(function ($record) {
                        if ($record->type === 'module') {
                            CourseModule::find($record->source_id)?->delete();
                        } else {
                            \App\Models\Evaluation::find($record->source_id)?->delete();
                        }
                    }),
            ])
            ->reorderable('order')
            ->defaultSort('order', 'asc');
    }

    public function reorderTable(array $order): void
    {
        // Fetch all items involved to identify type/source_id
        $items = \App\Models\CourseContent::whereIn('id', $order)->get()->keyBy('id');

        foreach ($order as $index => $id) {
            $newOrder = $index + 1;
            
            if (isset($items[$id])) {
                $item = $items[$id];
                
                if ($item->type === 'module') {
                    CourseModule::where('id', $item->source_id)->update(['order' => $newOrder]);
                } else {
                    \App\Models\Evaluation::where('id', $item->source_id)->update(['order' => $newOrder]);
                }
            }
        }
        
        // Optional: Notify user
        // \Filament\Notifications\Notification::make()->title('Orden actualizado')->success()->send(); 
        // Be careful with notifications during drag/drop as it might spam or glitch UI if rapid. 
        // Typically Filament provides its own feedback or it's instant.
    }
}
