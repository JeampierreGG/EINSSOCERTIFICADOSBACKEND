<?php

namespace App\Filament\Admin\Resources\PaymentFinalizedResource\Pages;

use App\Filament\Admin\Resources\PaymentFinalizedResource;
use App\Models\Certificate;
use App\Models\CertificateItem;
use App\Models\Institution;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Carbon\Carbon;

class EditProcessCertificate extends Page
{
    protected static string $resource = PaymentFinalizedResource::class;

    protected static string $view = 'filament.admin.resources.payment-finalized-resource.pages.edit-certificate';

    public Payment $record;

    public ?array $data = [];

    public function getTitle(): string 
    {
        return 'Editar Certificado';
    }

    public ?int $certificateId = null;

    public function mount(Payment $record): void
    {
        $this->record = $record;
        
        $certificate = Certificate::where('payment_id', $this->record->id)->latest()->first();

        if (!$certificate) {
            Notification::make()->title('Error')->body('Este pago no tiene un certificado asociado.')->danger()->send();
            $this->redirect(PaymentFinalizedResource::getUrl('index'));
            return;
        }

        $this->certificateId = $certificate->id;
        $items = [];
        if ($certificate->type === 'megapack') {
            $dbItems = $certificate->items()->orderBy('id')->get();
            foreach ($dbItems as $index => $item) {
                $items[] = [
                    'id' => $item->id,
                    'is_main' => ($index === 0), // First item is main
                    'institution_id' => $item->institution_id,
                    'title' => $item->title,
                    'category' => $item->category,
                    'hours' => $item->hours,
                    'grade' => $item->grade,
                    'issue_date' => $item->issue_date ? Carbon::parse($item->issue_date)->format('d/m/Y') : null,
                    'code' => $item->code,
                    'file_path' => $item->file_path,
                ];
            }
        }
        
        // Backward compatibility for type 'solo' -> map to 'cip' or 'einsso' based on institution_id
        $mappedType = $certificate->type;
        if ($mappedType === 'solo') {
            // Try to infer from institution
            if ($certificate->institution_id === Institution::cip()?->id) {
                $mappedType = 'cip';
            } elseif ($certificate->institution_id === Institution::einsso()?->id) {
                $mappedType = 'einsso';
            } else {
                // If neither (legacy data), maybe check name? Or default to einsso (modular) if no specific logic
                $instName = Institution::find($certificate->institution_id)?->name;
                if ($instName && (stripos($instName, 'Colegio') !== false || stripos($instName, 'CIP') !== false)) {
                     $mappedType = 'cip';
                } else {
                     $mappedType = 'einsso';
                }
            }
        }

        $this->form->fill([
            'type' => $mappedType,
            'institution_id' => $certificate->institution_id,
            'title' => $certificate->title,
            'category' => $certificate->category,
            'hours' => $certificate->hours,
            'grade' => $certificate->grade,
            'issue_date' => $certificate->issue_date ? Carbon::parse($certificate->issue_date)->format('d/m/Y') : null,
            'code' => $certificate->code,
            'file_path' => $certificate->file_path,
            'items' => $items,
        ]);
    }

    public function form(Form $form): Form
    {
        $paymentItems = is_string($this->record->items) ? json_decode($this->record->items, true) : $this->record->items;
        $paidTitle = $paymentItems['title'] ?? 'N/A';
        $paidCourse = $paymentItems['course_title'] ?? 'N/A';

        return $form
            ->schema([
                Forms\Components\Section::make('Editar Certificado para: ' . $this->record->user->name)
                    ->description(new \Illuminate\Support\HtmlString(
                        'DNI/CE: ' . ($this->record->user->profile->dni_ce ?? 'S/D') . '<br>' .
                        'Item Pagado: <strong>' . $paidTitle . '</strong><br>' .
                        'Curso: <strong>' . $paidCourse . '</strong>'
                    ))
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Tipo de Certificación')
                            ->options([
                                'cip'      => '🏛️ Colegio de Ingenieros del Perú',
                                'einsso'   => '📜 Einsso Consultores (Modular)',
                                'megapack' => '🎁 Megapack',
                            ])
                            ->inline()
                            ->live()
                            ->required()
                            ->disabled() // No permitir cambiar tipo en edición (complejo migrar datos)
                            ->dehydrated(),
                        
                        // --- SECCIÓN SOLO CERTIFICADO (CIP / EINSSO) ---
                        Forms\Components\Group::make()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => in_array($get('type'), ['cip', 'einsso']))
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('institution_id')
                                            ->label('Institución')
                                            ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->required()
                                            ->disabled() // Read-only
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('title')
                                            ->label('Título del curso / módulo')
                                            ->required()
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
                                            ->required(),
                                        Forms\Components\TextInput::make('hours')
                                            ->numeric()
                                            ->label('Horas')
                                            ->required(),
                                        Forms\Components\TextInput::make('grade')
                                            ->label('Nota')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->rules(['integer','between:0,20'])
                                            ->required(),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('issue_date')
                                            ->label('Fecha de emisión')
                                            ->required()
                                            ->mask('99/99/9999')
                                            ->placeholder('DD/MM/AAAA')
                                            ->rules(['date_format:d/m/Y']),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Código de certificado')
                                            ->required()
                                            ->unique(Certificate::class, 'code', ignorable: fn () => Certificate::find($this->certificateId))
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
                            ->visible(fn (Get $get) => $get('type') === 'megapack')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->label('Certificados del Pack')
                                    ->reorderable(false)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\Hidden::make('is_main'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('institution_id')
                                                    ->label('Institución')
                                                    ->options(fn () => Institution::query()->orderBy('name')->pluck('name', 'id')->all())
                                                    ->required()
                                                    ->disabled() // Read-only
                                                    ->dehydrated(),
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Título del curso / módulo')
                                                    ->required()
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
                                                    ->required(),
                                                Forms\Components\TextInput::make('hours')
                                                    ->numeric()
                                                    ->label('Horas')
                                                    ->required()
                                                    ->disabled(fn (Forms\Get $get) => $get('is_main'))
                                                    ->dehydrated(),
                                                Forms\Components\TextInput::make('grade')
                                                    ->label('Nota')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(20)
                                                    ->rules(['integer','between:0,20'])
                                                    ->required()
                                                    ->visible(fn (Forms\Get $get) => $get('category') !== 'modular'),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('issue_date')
                                                    ->label('Fecha de emisión')
                                                    ->required()
                                                    ->mask('99/99/9999')
                                                    ->placeholder('DD/MM/AAAA')
                                                    ->rules(['date_format:d/m/Y']),
                                                Forms\Components\TextInput::make('code')
                                                    ->label('Código de certificado')
                                                    ->required()
                                                    ->unique(CertificateItem::class, 'code', ignoreRecord: true)
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

    public function save(): void
    {
        $formData = $this->form->getState();
        $certificate = Certificate::find($this->certificateId);

        // Normalize type (legacy 'solo' -> new 'cip'/'einsso')
        // We cannot easily change the type in DB if it was 'solo', 
        // but we can update the other fields.
        // Ideally we should update the 'type' in DB to match new schema if it's 'solo'
        
        $dbData = [
            'institution_id' => $formData['institution_id'] ?? $certificate->institution_id,
            'title' => $formData['title'] ?? $certificate->title,
            'category' => $formData['category'] ?? $certificate->category,
            'hours' => $formData['hours'] ?? $certificate->hours,
            'grade' => $formData['grade'] ?? $certificate->grade,
            'issue_date' => isset($formData['issue_date']) && $formData['issue_date'] ? Carbon::createFromFormat('d/m/Y', $formData['issue_date'])->format('Y-m-d') : null,
            'code' => isset($formData['code']) ? strtoupper($formData['code']) : $certificate->code,
            'file_path' => $formData['file_path'] ?? $certificate->file_path,
        ];
        
        // If the form type is one of the new ones and DB is 'solo', update it
        if ($formData['type'] !== 'megapack' && $certificate->type === 'solo') {
             $dbData['type'] = $formData['type']; // 'cip' or 'einsso'
        }

        if ($formData['type'] !== 'megapack') {
            $certificate->update($dbData);
        } else {
            // For megapack, we update items
            foreach ($formData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $item = CertificateItem::find($itemData['id']);
                    if ($item) {
                        $item->update([
                            // institution_id is read-only in form, assume it doesn't change or use value if present
                            'institution_id' => $itemData['institution_id'] ?? $item->institution_id,
                            'title' => $itemData['title'],
                            'category' => $itemData['category'],
                            'hours' => $itemData['hours'] ?? 0,
                            'grade' => $itemData['grade'] ?? 0,
                            'issue_date' => $itemData['issue_date'] ? Carbon::createFromFormat('d/m/Y', $itemData['issue_date'])->format('Y-m-d') : null,
                            'code' => strtoupper($itemData['code']),
                            'file_path' => $itemData['file_path'] ?? $item->file_path,
                        ]);
                    }
                }
            }
        }

        Notification::make()
            ->title('Certificado actualizado exitosamente')
            ->success()
            ->send();

        $this->redirect(PaymentFinalizedResource::getUrl('index'));
    }
}
