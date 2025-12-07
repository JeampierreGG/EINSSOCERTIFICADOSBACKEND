<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Certificate;
use App\Models\CertificateItem;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $users = User::query()->where('role_id', 2)->where('is_admin', false)->count();
        $solo = Certificate::query()->where('type', 'solo')->count();
        $megapacks = Certificate::query()->where('type', 'megapack')->count();
        $assignedTotal = $solo + CertificateItem::query()->count();

        return [
            Stat::make('Usuarios', (string) $users),
            Stat::make('Certificados asignados', (string) $assignedTotal),
            Stat::make('Solo certificados', (string) $solo),
            Stat::make('Megapacks', (string) $megapacks),
        ];
    }
}

