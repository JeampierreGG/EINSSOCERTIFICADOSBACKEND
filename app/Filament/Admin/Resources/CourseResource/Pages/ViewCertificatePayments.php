<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\CertificationBlock;
use App\Models\Course;
use App\Models\CourseCertificateOption;
use App\Models\Payment;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewCertificatePayments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.view-certificate-payments';

    public Course $record;
    public CourseCertificateOption $certificateOption;

    public function mount(Course $record, $certOptionId): void
    {
        $this->record = $record;
        $this->certificateOption = CourseCertificateOption::findOrFail($certOptionId);
        
        if ($this->certificateOption->course_id !== $record->id) {
            abort(404);
        }
    }

    public function getTitle(): string
    {
        return "Pagos: {$this->certificateOption->title}";
    }

    public function getSubheading(): ?string
    {
        $course = $this->record->title;
        $type = match ($this->certificateOption->type) {
            'cip' => 'Colegio de Ingenieros',
            'einsso' => 'Einsso Consultores',
            'megapack' => 'Mega Pack',
            'solo_certificado' => 'Solo Certificado',
            default => $this->certificateOption->type,
        };
        return "Curso: {$course} — Tipo: {$type}";
    }
    
    public function table(Table $table): Table
    {
        // Get block IDs assigned to this certificate option (could be through current or historical blocks)
        $certOptionId = $this->certificateOption->id;
        
        return $table
            ->query(
                Payment::query()
                    ->where('course_id', $this->record->id)
                    ->whereNotNull('items')
                    ->whereRaw("items->>'id' = ?", [(string) $certOptionId])
                    ->with(['user', 'user.profile', 'certificationBlock'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('certificate_type')
                    ->label('Tipo de Certificado')
                    ->getStateUsing(function (Payment $record) {
                        $items = $record->items;
                        if (is_array($items)) {
                             return $items['title'] ?? ' - ';
                        }
                        return ' - ';
                    }),

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
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'pending' => 'Pendiente', 
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
                Tables\Filters\SelectFilter::make('certification_block_id')
                    ->label('Bloque')
                    ->native(false)
                    ->searchable()
                    ->options(function () {
                        $blocks = CertificationBlock::where('course_id', $this->record->id)
                            ->orderBy('start_date', 'desc')
                            ->pluck('name', 'id')
                            ->toArray();
                        
                        // Use array union to preserve numeric keys
                        return ['sin_bloque' => 'Sin bloque'] + $blocks;
                    })
                    ->query(function ($query, array $data) {
                        // If no value selected, return all
                        if (empty($data['value'])) {
                            return $query;
                        }
                        // If "Sin bloque" selected, filter logic
                        if ($data['value'] === 'sin_bloque') {
                            return $query->whereNull('certification_block_id');
                        }
                        // Otherwise filter by specific block ID
                        return $query->where('certification_block_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->native(false)
                    ->options([
                        'approved' => 'Aprobado',
                        'pending' => 'Pendiente',
                        'rejected' => 'Rechazado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                         $certOption = $livewire->certificateOption;
                         $filename = 'Pagos ' . preg_replace('/[^A-Za-z0-9\-\s]/', '', $certOption->title) . '.xls';
                         
                         // Get the query with current filters applied
                         $query = $livewire->getFilteredTableQuery()->clone();
                         
                         // Remove pagination and ordering to get all matching results
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
            ->actions([]);
    }
}
