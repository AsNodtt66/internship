<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\GmDepartmentUniversityWidget;
use App\Filament\Widgets\GmMonthlyChartWidget;
use App\Filament\Widgets\GmPendingApprovalsWidget;
use App\Filament\Widgets\GmRecentActivityWidget;
use App\Filament\Widgets\GmStatsOverview;
use App\Filament\Widgets\GmWorkflowFunnelWidget;
use App\Filament\Widgets\KepalaBagianStatsWidget;
use App\Filament\Widgets\PengajuanStatsWidget;
use App\Filament\Widgets\PerluTindakanWidget;
use App\Filament\Widgets\PesertaAktifTableWidget;
use App\Filament\Widgets\PesertaSelesaiTableWidget;
use App\Filament\Widgets\RecentActivityWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('Sistem Magang & Penelitian')
            ->brandLogo(fn () => asset('images/logo-rajawali.png'))
            ->brandLogoHeight('2.5rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Inter')
            ->strictAuthorization()
            ->colors([
                'primary' => Color::hex('#1B5A96'),
                'success' => Color::hex('#22C55E'),
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                \Filament\View\PanelsRenderHook::SIDEBAR_START,
                fn () => view('filament.hooks.brand'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Daftarkan HANYA widget interaktif yang kita inginkan di sini
            ->widgets([
                PengajuanStatsWidget::class,
                KepalaBagianStatsWidget::class,
                PerluTindakanWidget::class,
                RecentActivityWidget::class,
                PesertaAktifTableWidget::class,
                PesertaSelesaiTableWidget::class,
                // Executive dashboard khusus role 'gm' — masing-masing widget
                // punya canView() sendiri sehingga hanya tampil untuk GM.
                GmStatsOverview::class,
                GmMonthlyChartWidget::class,
                GmWorkflowFunnelWidget::class,
                GmPendingApprovalsWidget::class,
                GmDepartmentUniversityWidget::class,
                GmRecentActivityWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                class_exists(PreventRequestForgery::class) ? PreventRequestForgery::class : VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Pengaman: jika browser (extension/antivirus) memblokir 'eval'
            // sehingga Alpine.js gagal boot, elemen x-cloak tidak akan
            // tersembunyi permanen. Lihat resources/views/filament/csp-fallback.blade.php
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.csp-fallback')->render(),
            );
    }
}