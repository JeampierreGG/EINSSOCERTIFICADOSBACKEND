<?php

namespace App\Providers\Filament;

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
            ->login()
            ->brandName('EINSSO | Dashboard')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')

            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Gestión Académica'),
                NavigationGroup::make()
                    ->label('Pagos'),
                NavigationGroup::make()
                    ->label('Seguridad'),
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
            ])
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'HTML'
                    <style>
                        /* ============================================================
                           DARK MODE FIX: Pagination "Records per page" select
                           El <select> de Filament hereda bg-transparent, lo que hace
                           que el navegador aplique su propio fondo blanco nativo.
                           Forzamos el fondo correcto y color-scheme para que las
                           opciones nativas del dropdown también sean oscuras.
                           ============================================================ */

                        /* Wrapper del select de paginación */
                        html.dark .fi-pagination .fi-input-wrapper,
                        html.dark nav[aria-label] .fi-input-wrapper {
                            background-color: rgb(30 41 59) !important;
                        }

                        /* El <select> en sí */
                        html.dark .fi-pagination .fi-select-input,
                        html.dark .fi-pagination select,
                        html.dark nav[aria-label] .fi-select-input,
                        html.dark nav[aria-label] select {
                            background-color: rgb(30 41 59) !important;
                            color: rgb(248 250 252) !important;
                            color-scheme: dark !important;
                        }

                        /* Las options nativas */
                        html.dark .fi-pagination select option,
                        html.dark nav[aria-label] select option {
                            background-color: rgb(30 41 59) !important;
                            color: rgb(248 250 252) !important;
                        }
                    </style>
                HTML,
            );
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('filament-custom', asset('css/filament-custom.css?v=8')),
        ]);
    }
}
