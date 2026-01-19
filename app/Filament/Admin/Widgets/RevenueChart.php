<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Tendencia de Ingresos (Últimos 30 días)';
    protected static ?int $sort = 2;
    protected static string $color = 'success';

    protected function getData(): array
    {
        // Calculate daily revenue for the last 30 days
        $data = DB::table('payments')
            ->select(DB::raw('created_at::date as date'), DB::raw('SUM(amount) as total'))
            ->where('status', 'approved')
            ->where('created_at', '>=', Carbon::now()->subDays(31))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        // Fill gaps in dates
        $startDate = Carbon::now()->subDays(30);
        for ($i = 0; $i <= 30; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $startDate->copy()->addDays($i)->translatedFormat('d M');
            
            $match = $data->firstWhere('date', $date);
            $values[] = $match ? (float) $match->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos (S/)',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
