<?php

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return CertificateResource::getUrl('index');
    }

    public function form(Form $form): Form
    {
        $record = $this->record;
        if ($record && $record->type === 'megapack') {
            return $form
                ->model($record)
                ->statePath('data')
                ->schema([
                    Forms\Components\Grid::make(4)
                        ->schema([
                            Forms\Components\TextInput::make('student_name')
                                ->label('Nombres y Apellidos')
                                ->disabled(true)
                                ->afterStateHydrated(function ($state, \Filament\Forms\Set $set) use ($record) {
                                    $set('student_name', optional($record->user)->name);
                                }),
                            Forms\Components\TextInput::make('dni_ce')
                                ->label('DNI/CE')
                                ->maxLength(20)
                                ->disabled(true)
                                ->afterStateHydrated(function ($state, \Filament\Forms\Set $set) use ($record) {
                                    $profile = optional(optional($record->user)->profile);
                                    $set('dni_ce', $profile->dni_ce ?? null);
                                }),
                            Forms\Components\Radio::make('type')
                                ->label('Tipo')
                                ->options([
                                    'solo' => 'Solo certificado',
                                    'megapack' => 'Megapack',
                                ])
                                ->inline()
                                ->disabled(true),
                        ]),
                    Forms\Components\Group::make()
                        ->columnSpanFull()
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship('items')
                                ->label('Certificados del Megapack')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('institution_id')
                                                ->label('Institución')
                                                ->options(fn () => \App\Models\Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                            Forms\Components\TextInput::make('title')->label('Título del curso / módulo')->required(),
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
                                            Forms\Components\TextInput::make('hours')->numeric()->label('Horas')->required(),
                                            Forms\Components\TextInput::make('grade')
                                                ->label('Nota')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(20)
                                                ->rules(['integer','between:0,20'])
                                                ->live()
                                                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                                    $v = is_numeric($state) ? (int) $state : 0;
                                                    if ($v < 0) $v = 0;
                                                    if ($v > 20) $v = 20;
                                                    $set('grade', $v);
                                                })
                                                ->dehydrateStateUsing(fn ($state) => max(0, min(20, (int) $state)))
                                                ->extraAttributes(['inputmode' => 'numeric']),
                                        ]),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\DatePicker::make('issue_date')->label('Fecha de emisión')->required(),
                                            Forms\Components\TextInput::make('code')->label('Código de certificado')->required(),
                                            Forms\Components\FileUpload::make('file_path')
                                                ->label('Archivo')
                                                ->disk(config('filesystems.default'))
                                                ->directory('certificates')
                                                ->acceptedFileTypes(['application/pdf','image/png','image/jpeg','image/jpg','image/webp'])
                                                ->visibility('private')
                                                ->required()
                                                ->maxSize(10240),
                                        ]),
                                ])
                                ->grid(1),
                        ]),
                ]);
        }

        return parent::form($form);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Con Repeater->relationship('items'), Filament gestiona la persistencia.
        return $data;
    }
}
