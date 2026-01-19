<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Models\CertificationBlock;
use App\Models\Course;
use App\Models\Payment;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewBlockPayments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.view-block-payments';

    public Course $record;
    public CertificationBlock $block;

    public function mount(Course $record, $blockId): void
    {
        $this->record = $record;
        $this->block = CertificationBlock::findOrFail($blockId);
        
        if ($this->block->course_id !== $record->id) {
            abort(404);
        }
    }

    public function getTitle(): string
    {
        return "Pagos del Bloque: {$this->block->name}";
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('certification_block_id', $this->block->id)
                    ->with(['user', 'user.profile'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('certificate_type')
                    ->label('Tipo de Certificado')
                    ->getStateUsing(function (Payment $record) {
                        $items = $record->items;
                        // Checkout sends a single object as items
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
                    
                Tables\Columns\TextColumn::make('date_paid')
                    ->label('Fecha Pago')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                         $block = $livewire->block;
                         $filename = 'Lista ' . preg_replace('/[^A-Za-z0-9\-\s]/', '', $block->name) . '.xls';
                         
                         return response()->streamDownload(function () use ($block) {
                            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
                            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
                            echo '<body>';
                            echo '<table border="1" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">';
                            
                            // Header
                            echo '<thead style="background-color: #282975; color: #ffffff;">';
                            echo '<tr>';
                            $headers = ['Usuario', 'DNI/CE', 'Teléfono', 'Correo', 'Tipo Certificado', 'Estado', 'Monto', 'Fecha Pago'];
                            foreach ($headers as $header) {
                                echo '<th style="background-color: #282975; color: #ffffff; padding: 10px; font-weight: bold; border: 1px solid #000000;">' . $header . '</th>';
                            }
                            echo '</tr>';
                            echo '</thead>';
                            
                            // Body
                            echo '<tbody>';
                            
                            Payment::query()
                                ->where('certification_block_id', $block->id)
                                ->with(['user', 'user.profile'])
                                ->chunk(100, function ($payments) {
                                    foreach ($payments as $payment) {
                                        $items = is_string($payment->items) ? json_decode($payment->items, true) : $payment->items;
                                        $certType = is_array($items) ? ($items['title'] ?? '') : '';
                                        
                                        $statusLabel = match ($payment->status) {
                                            'approved' => 'Aprobado',
                                            'rejected' => 'Rechazado',
                                            'pending' => 'Pendiente',
                                            default => $payment->status
                                        };
                                        
                                        $statusColor = match ($payment->status) {
                                            'approved' => '#dcfce7', // green-100
                                            'rejected' => '#fee2e2', // red-100
                                            'pending' => '#dbeafe', // blue-100
                                            default => '#ffffff'
                                        };

                                        echo '<tr>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . ($payment->user?->name ?? 'Usuario Eliminado') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->user?->profile?->dni_ce ?? '') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->user?->profile?->phone ?? '') . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . ($payment->user?->email ?? $payment->payer_email) . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000;">' . $certType . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; background-color: ' . $statusColor . '; text-align: center;">' . $statusLabel . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: right;">S/ ' . number_format($payment->amount, 2) . '</td>';
                                        echo '<td style="padding: 8px; border: 1px solid #000000; text-align: center;">' . ($payment->date_paid ? \Carbon\Carbon::parse($payment->date_paid)->format('d/m/Y') : '') . '</td>';
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
