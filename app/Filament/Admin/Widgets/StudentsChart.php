<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentsChart extends ChartWidget
{
    protected static ?string $heading = 'Nuevos Estudiantes (Últimos 12 meses)';
    protected static ?int $sort = 3;
    protected static string $color = 'info';

    protected function getData(): array
    {
        $data = DB::table('users')
            ->select(DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
            ->where('role_id', 2)
            ->where('is_admin', 0)
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Note: strftime is for SQLite. If the user uses MySQL/PostgreSQL, I should adjust.
        // Assuming MySQL since it's common, but I'll use a more generic way if possible or just check.
        // Let's check the DB connection.
        
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $labels[] = $monthDate->translatedFormat('M Y');
            
            $match = $data->firstWhere('month', $monthKey);
            $values[] = $match ? (int) $match->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Estudiantes registrados',
                    'data' => $values,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
