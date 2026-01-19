<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\CertificationBlock;
use App\Models\Course;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables;
use Filament\Tables\Table;

class ManageCourseBlocks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-blocks';

    public Course $record;

    public function mount(Course $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Gestión de Bloques de Certificación';
    }

    protected function getBlockForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nombre del Bloque / Campaña')
                ->required()
                ->maxLength(255)
                ->placeholder('Ej: Campaña Lanzamiento, Bloque Enero 2026'),
            
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Fecha de Inicio')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(),
                    
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Fecha de Fin')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->afterOrEqual('start_date')
                        ->native(),
                ]),
            
            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->helperText('Solo los bloques activos son visibles para los usuarios')
                ->inline(false),
            
            Forms\Components\Section::make('Opciones de Certificación Disponibles')
                ->description('Seleccione las opciones de certificación que estarán disponibles para compra durante este bloque')
                ->schema([
                    Forms\Components\CheckboxList::make('certificate_option_ids')
                        ->label('')
                        ->options(function () {
                            return \App\Models\CourseCertificateOption::where('course_id', $this->record->id)
                                ->get()
                                ->mapWithKeys(function ($option) {
                                    $type = $option->type === 'megapack' ? '🎁 Megapack' : '📜 Solo Certificado';
                                    $price = 'S/ ' . number_format($option->price, 2);
                                    return [$option->id => "{$type} - {$option->title} ({$price})"];
                                });
                        })
                        ->columns(1)
                        ->searchable()
                        ->bulkToggleable()
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record) {
                                // Load current certificate options assigned to this block
                                $optionIds = \App\Models\CourseCertificateOption::where('certification_block_id', $record->id)
                                    ->pluck('id')
                                    ->toArray();
                                $component->state($optionIds);
                            }
                        }),
                ])
                ->collapsible(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CertificationBlock::query()->where('course_id', $this->record->id))
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('start_date')->date('d/m/Y')->label('Inicio')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date('d/m/Y')->label('Fin')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Bloque')
                    ->modalHeading('Crear Bloque de Disponibilidad')
                    ->closeModalByClickingAway(false)
                    ->form($this->getBlockForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['course_id'] = $this->record->id;
                        return $data;
                    })
                    ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                        // Extract certificate option IDs before creating
                        $certificateOptionIds = $data['certificate_option_ids'] ?? [];
                        unset($data['certificate_option_ids']);
                        
                        // Create the block
                        $block = $model::create($data);
                        
                        // Assign selected certificate options to this block
                        if (!empty($certificateOptionIds)) {
                            \App\Models\CourseCertificateOption::whereIn('id', $certificateOptionIds)
                                ->update(['certification_block_id' => $block->id]);
                        }
                        
                        return $block;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('payments')
                    ->label('Pagos')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn (CertificationBlock $record): string => \App\Filament\Admin\Resources\CourseResource::getUrl('block-payments', ['record' => $this->record->id, 'blockId' => $record->id])),
                
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Bloque de Disponibilidad')
                    ->closeModalByClickingAway(false)
                    ->form($this->getBlockForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        // Store certificate option IDs separately
                        $data['_certificate_option_ids'] = $data['certificate_option_ids'] ?? [];
                        unset($data['certificate_option_ids']);
                        return $data;
                    })
                    ->action(function ($record, array $data) {
                        // Extract the certificate option IDs
                        $newOptionIds = $data['_certificate_option_ids'] ?? [];
                        unset($data['_certificate_option_ids']);
                        
                        // Update block data
                        $record->update($data);
                        
                        // Remove this block from options that are no longer selected
                        \App\Models\CourseCertificateOption::where('certification_block_id', $record->id)
                            ->whereNotIn('id', $newOptionIds)
                            ->update(['certification_block_id' => null]);
                        
                        // Assign this block to newly selected options
                        if (!empty($newOptionIds)) {
                            \App\Models\CourseCertificateOption::whereIn('id', $newOptionIds)
                                ->update(['certification_block_id' => $record->id]);
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        // Remove block assignment from certificate options when deleting
                        \App\Models\CourseCertificateOption::where('certification_block_id', $record->id)
                            ->update(['certification_block_id' => null]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
