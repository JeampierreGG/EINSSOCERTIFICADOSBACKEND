<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2.5rem')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')

            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->spa()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Gestión Académica'),
                NavigationGroup::make()
                    ->label('Pagos'),
                NavigationGroup::make()
                    ->label('Soporte'),
                NavigationGroup::make()
                    ->label('Ajustes'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                \App\Filament\Admin\Widgets\DashboardOverview::class,
                \App\Filament\Admin\Widgets\RevenueChart::class,
                \App\Filament\Admin\Widgets\StudentsChart::class,
                \App\Filament\Admin\Widgets\TopCoursesChart::class,
                \App\Filament\Admin\Widgets\PendingPayments::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => '<link rel="stylesheet" href="/css/app/filament-custom.css?v=2" />',
        );
    }
}
