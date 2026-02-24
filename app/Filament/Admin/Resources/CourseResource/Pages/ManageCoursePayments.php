<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\CertificationBlock;
use App\Models\Course;
use App\Models\CourseCertificateOption;
use App\Models\Payment;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ManageCoursePayments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-payments';

    public Course $record;

    public function mount(Course $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return "Gestión de Pagos: {$this->record->title}";
    }
    
    public function table(Table $table): Table
    {
         return $table
            ->query(
                Payment::query()
                    ->where('course_id', $this->record->id)
                    ->with(['user', 'user.profile', 'certificationBlock', 'paymentMethod'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('certificate_type')
                    ->label('Opción de Certificación')
                    ->getStateUsing(function (Payment $record) {
                        $items = is_string($record->items) ? json_decode($record->items, true) : $record->items;
                        if (is_array($items)) {
                             return $items['title'] ?? ' - ';
                        }
                        return ' - ';
                    })
                    ->searchable(['items']), // Búsqueda simple en JSON puede no funcionar tan bien en todos los DBs, pero intentamos

                Tables\Columns\TextColumn::make('user.profile.dni_ce')
                    ->label('DNI/CE')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.profile.phone')
                    ->label('Teléfono'),
                    
                Tables\Columns\TextColumn::make('email_display')
                    ->label('Correo')
                    ->getStateUsing(fn (Payment $record) => $record->user?->email ?? $record->payer_email)
                    ->searchable(['user.email', 'payer_email']),

                Tables\Columns\TextColumn::make('certificationBlock.name')
                    ->label('Bloque')
                    ->default('Sin bloque')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sin bloque' ? 'gray' : 'info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                     ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        'finalized' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'pending' => 'Pendiente', 
                        'finalized' => 'Finalizado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('PEN'),
                    
                Tables\Columns\TextColumn::make('date_paid')
                    ->label('Fecha Pago')
                    ->dateTime('d/m/Y H:i:s', 'America/Lima'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('certificate_option_id')
                    ->label('Opción de Certificación')
                    ->native(false)
                    ->searchable()
                    ->options(function () {
                        return CourseCertificateOption::where('course_id', $this->record->id)
                            ->pluck('title', 'id');
                    })
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        // Filtrar por ID dentro del JSON items
                        return $query->whereRaw("items->>'id' = ?", [(string) $data['value']]);
                    }),

                Tables\Filters\SelectFilter::make('certification_block_id')
                    ->label('Bloque')
                    ->native(false)
                    ->searchable()
                    ->options(function () {
                        $blocks = CertificationBlock::where('course_id', $this->record->id)
                            ->orderBy('start_date', 'desc')
                            ->pluck('name', 'id')
                            ->toArray();
                        
                        return ['sin_bloque' => 'Sin bloque'] + $blocks;
                    })
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        if ($data['value'] === 'sin_bloque') {
                            return $query->whereNull('certification_block_id');
                        }
                        return $query->where('certification_block_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->native(false)
                    ->options([
                        'approved' => 'Aprobado',
                        'pending' => 'Pendiente',
                        'rejected' => 'Rechazado',
                        'finalized' => 'Finalizado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                         $courseTitle = $livewire->record->title;
                         $filename = 'Pagos_' . preg_replace('/[^A-Za-z0-9\-\s]/', '', substr($courseTitle, 0, 20)) . '_' . date('Ymd_His') . '.xls';
                         
                         $query = $livewire->getFilteredTableQuery()->clone();
                         $query->reorder();
                         
                         return response()->streamDownload(function () use ($query) {
                            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
                            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
                            echo '<body>';
                            echo '<table border="1" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">';
                            
                            // Header
                            echo '<thead style="background-color: #282975; color: #ffffff;">';
                            echo '<tr>';
                            $headers = ['Usuario', 'DNI/CE', 'Teléfono', 'Correo', 'Tipo Certificado', 'Bloque', 'Estado', 'Monto', 'Fecha Pago'];
                            foreach ($headers as $header) {
                                echo '<th style="background-color: #282975; color: #ffffff; padding: 10px; font-weight: bold; border: 1px solid #000000;">' . $header . '</th>';
                            }
                            echo '</tr>';
                            echo '</thead>';
                            
                            // Body
                            echo '<tbody>';
                            
                            $query->chunk(100, function ($payments) {
                                foreach ($payments as $payment) {
                                        $items = is_string($payment->items) ? json_decode($payment->items, true) : $payment->items;
                                        $certType = is_array($items) ? ($items['title'] ?? '') : '';
                                        $blockName = $payment->certificationBlock?->name ?? 'Sin bloque';
                                        
                                        $statusLabel = match ($payment->status) {
                                            'approved' => 'Aprobado',
                                            'rejected' => 'Rechazado',
                                            'pending' => 'Pendiente',
                                            default => $payment->status
                                        };
                                        
                                        $statusColor = match ($payment->status) {
                                            'approved' => '#dcfce7',
                                            'rejected' => '#fee2e2',
                                            'pending' => '#dbeafe',
                                            default => '#ffffff'
                                        };

                                        echo '<tr>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . ($payment->user?->name ?? 'Usuario Eliminado') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->user?->profile?->dni_ce ?? '') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->user?->profile?->phone ?? '') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . ($payment->user?->email ?? $payment->payer_email) . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . $certType . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . $blockName . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; background-color: ' . $statusColor . '; text-align: center;">' . $statusLabel . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: right;">S/ ' . number_format($payment->amount, 2) . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->date_paid ? \Carbon\Carbon::parse($payment->date_paid)->timezone('America/Lima')->format('d/m/Y H:i:s') : '') . '</td>';
                                        echo '</tr>';
                                    }
                                });
                                
                            echo '</tbody>';
                            echo '</table>';
                            echo '</body></html>';
                         }, $filename);
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Revisar')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Payment $record) => 
                        ($record->user->name ?? 'Usuario') . ' | ' . 
                        ($record->user->profile->dni_ce ?? 'S/D')
                    )
                    ->modalWidth('6xl')
                    ->closeModalByClickingAway(false)
                    ->form(function (Payment $record) {
                        $items = is_string($record->items) ? json_decode($record->items, true) : $record->items;
                        $certTitle = $items['title'] ?? 'N/A';
                        
                        return [
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Section::make('Información del Comprador')->schema([
                                        Forms\Components\TextInput::make('payer_first_name')
                                            ->label('Nombres')
                                            ->default($record->payer_first_name ?? '-')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('payer_last_name')
                                            ->label('Apellidos')
                                            ->default($record->payer_last_name ?? '-')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('payer_email')
                                            ->label('Correo para certificado')
                                            ->default($record->payer_email ?? '-')
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                    Forms\Components\Section::make('Detalles Financieros')->schema([
                                        Forms\Components\TextInput::make('method')
                                            ->label('Método')
                                            ->default($record->paymentMethod->name ?? 'N/A')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('amount_view')
                                            ->label('Monto Pagado')
                                            ->default('S/ ' . number_format($record->amount, 2))
                                            ->disabled(),
                                        
                                        Forms\Components\TextInput::make('code')
                                            ->label('Cód. Operación / Fecha')
                                            ->default(($record->transaction_code ?? '-') . ' / ' . ($record->date_paid ? $record->date_paid->format('d/m/Y H:i:s') : '-'))
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])->columns(2),
                                ])->columnSpan(1),

                                Forms\Components\Group::make([
                                    Forms\Components\Section::make('Lo que está pagando')->schema([
                                        Forms\Components\TextInput::make('cert')
                                            ->label('Opción Certificación')
                                            ->default($certTitle)
                                            ->disabled(),
                                        Forms\Components\TextInput::make('block_view')
                                            ->label('Bloque / Campaña')
                                            ->default($record->certificationBlock->name ?? 'No especificado')
                                            ->disabled(),
                                    ])->columns(1),

                                    Forms\Components\Section::make('Comprobante (Voucher)')->schema([
                                        Forms\Components\Placeholder::make('proof_image_view')
                                            ->label('')
                                            ->content(function (Payment $record) {
                                                if (!$record->proof_image_path) return 'Sin imagen';
                                                $url = \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($record->proof_image_path, now()->addMinutes(20));
                                                return new \Illuminate\Support\HtmlString('<a href="'.$url.'" target="_blank" class="text-blue-600 hover:underline">Ver Imagen Completa</a><br><img src="'.$url.'" style="max-height: 200px; width: auto;" class="mt-2 rounded shadow"/>');
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                ])->columnSpan(1),
                            ]),
                        ];
                    })
                    ->action(function (Payment $record) {
                        $record->update(['status' => 'approved']);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Pago Aprobado')
                            ->body('El pago ha sido aprobado correctamente.')
                            ->send();
                    })
                    ->modalSubmitActionLabel('Aprobar Pago')
                    ->visible(fn (Payment $record) => in_array($record->status, ['pending', 'rejected']))
                    ->extraModalFooterActions(function (Payment $record) {
                        return [
                            Tables\Actions\Action::make('reject_payment')
                                ->label('Rechazar')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->form([
                                    Forms\Components\Textarea::make('reason')
                                        ->label('Motivo del rechazo')
                                        ->required()
                                ])
                                ->action(function (array $data) use ($record) {
                                    $record->update([
                                        'status' => 'rejected', 
                                        'admin_note' => $data['reason']
                                    ]);
                                    \Filament\Notifications\Notification::make()->danger()->title('Pago Rechazado')->send();
                                })
                                ->cancelParentActions()
                        ];
                    }),

                Tables\Actions\Action::make('change_status')
                    ->label('Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                'approved' => 'Aprobado',
                                'rejected' => 'Rechazado',
                                'pending' => 'Pendiente',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->visible(fn (Payment $record) => $record->status === 'approved' && !$record->certificate()->exists())
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Estado actualizado')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('certificate')
                    ->label('Certificado')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->url(fn (Payment $record) => \App\Filament\Admin\Resources\CourseResource::getUrl('payments.process', ['record' => $record->course_id, 'payment' => $record->id]))
                    ->visible(fn (Payment $record) => $record->status === 'approved' && !$record->certificate()->exists()),

                Tables\Actions\Action::make('edit_finalized')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Payment $record) => \App\Filament\Admin\Resources\PaymentFinalizedResource::getUrl('edit_certificate', ['record' => $record]))
                    ->visible(fn (Payment $record) => $record->status === 'finalized' || $record->certificate()->exists()),
            ]);
    }
}
