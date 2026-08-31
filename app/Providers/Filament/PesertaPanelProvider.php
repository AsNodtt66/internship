<?php

namespace App\Providers\Filament;

use App\Filament\Peserta\Pages\Auth\EditProfile; // <-- Edit profil custom peserta
use App\Filament\Peserta\Pages\Auth\Register; // <-- Registrasi custom peserta
use App\Filament\Peserta\Pages\Dashboard; // <-- Dashboard custom peserta
use App\Filament\Peserta\Pages\DokumenSaya;
use App\Filament\Peserta\Pages\JadwalPkl;
use App\Filament\Peserta\Pages\NotifikasiSaya;
use App\Filament\Peserta\Resources\PengajuanPenelitianResource;
use App\Filament\Peserta\Resources\PengajuanPklResource;
use App\Filament\Peserta\Resources\PengajuanResource; // <-- Resource generik, tetap didaftarkan agar route view/edit lama (dipakai Dashboard & QuickActions) tetap ada, tapi navigasinya disembunyikan (lihat shouldRegisterNavigation di PengajuanResource)
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

class PesertaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('peserta')
            ->path('peserta')
            ->login()
            ->registration(Register::class)
            ->profile(EditProfile::class)
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
                PanelsRenderHook::SIDEBAR_START,
                fn () => view('filament.hooks.brand'),
            )
            // Pendaftaran manual resource agar dipaksa muncul di sidebar.
            // Menu peserta dipisah jadi 2: "Pengajuan PKL/Magang" dan
            // "Pengajuan Penelitian" (masing-masing subclass PengajuanResource,
            // reuse form/table/policy -- tidak ada logic backend yang
            // diduplikasi). PengajuanResource generik tetap didaftarkan
            // supaya route-nya hidup, tapi disembunyikan dari sidebar.
            ->resources([
                PengajuanResource::class,
                PengajuanPklResource::class,
                PengajuanPenelitianResource::class,
            ])
            ->pages([
                Dashboard::class,
                DokumenSaya::class,
                JadwalPkl::class,
                NotifikasiSaya::class,
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