<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopCoursesChart extends ChartWidget
{
    protected static ?string $heading = 'Cursos con Mayores Ingresos';
    protected static ?int $sort = 5;
    protected static string $color = 'warning';

    protected function getData(): array
    {
        $data = DB::table('payments')
            ->join('courses', 'payments.course_id', '=', 'courses.id')
            ->select('courses.title', DB::raw('SUM(payments.amount) as total'))
            ->where('payments.status', 'approved')
            ->groupBy('courses.id', 'courses.title')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Recaudado (S/)',
                    'data' => $data->pluck('total')->map(fn($v) => (float)$v)->toArray(),
                    'backgroundColor' => [
                        '#6366f1', // Indigo
                        '#10b981', // Success
                        '#f59e0b', // Warning
                        '#ef4444', // Danger
                        '#8b5cf6', // Violet
                    ],
                ],
            ],
            'labels' => $data->pluck('title')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
