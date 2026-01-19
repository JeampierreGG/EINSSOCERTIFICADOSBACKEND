<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Certificate;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DashboardOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Students Count
        $usersCount = User::query()->where('role_id', 2)->where('is_admin', false)->count();
        $newUsersThisMonth = User::query()
            ->where('role_id', 2)
            ->where('is_admin', false)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Payments Stats
        $pendingPayments = Payment::query()->where('status', 'pending')->count();
        $rejectedPayments = Payment::query()->where('status', 'rejected')->count();
        $totalRevenue = Payment::query()->where('status', 'approved')->sum('amount');
        
        // Revenue this month
        $revenueThisMonth = Payment::query()
            ->where('status', 'approved')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        // Certificates Stats
        $totalCerts = Certificate::query()->count();
        $certsToday = Certificate::query()->whereDate('created_at', Carbon::today())->count();

        return [
            Stat::make('Ingresos Totales', 'S/ ' . number_format($totalRevenue, 2))
                ->description('S/ ' . number_format($revenueThisMonth, 2) . ' este mes')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 3, 4, 5, 6, 3, 5, 2, 3, 9])
                ->color('success'),

            Stat::make('Pagos Pendientes', (string) $pendingPayments)
                ->description($pendingPayments > 0 ? 'Requiere aprobación' : 'Al día')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 5 ? 'danger' : ($pendingPayments > 0 ? 'warning' : 'success')),

            Stat::make('Pagos Rechazados', (string) $rejectedPayments)
                ->description('Conversión perdida')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),

            Stat::make('Estudiantes Totales', (string) $usersCount)
                ->description($newUsersThisMonth . ' nuevos este mes')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
