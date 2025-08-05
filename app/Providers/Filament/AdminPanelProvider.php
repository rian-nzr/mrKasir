<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
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
                \App\Http\Middleware\StoreMiddleware::class,
                'ensure.store.selected',
            ])
            ->userMenuItems([
                'store_info' => MenuItem::make()
                    ->label(function () {
                        $user = auth()->user();
                        if ($user?->isSuperAdmin()) {
                            $storeId = session('selected_store_id');
                            if ($storeId) {
                                $store = \App\Models\Store::find($storeId);
                                return 'Aktif: ' . $store?->name . ' (' . $store?->code . ')';
                            }
                            return 'Super Admin - Pilih Toko';
                        }
                        return $user?->store ? 'Toko: ' . $user->store->name : 'Tidak ada toko';
                    })
                    ->icon('heroicon-o-building-storefront')
                    ->color(function () {
                        $user = auth()->user();
                        if ($user?->isSuperAdmin()) {
                            return session('selected_store_id') ? 'success' : 'warning';
                        }
                        return $user?->store ? 'success' : 'danger';
                    })
                    ->sort(-1),
                                'change_store' => MenuItem::make()
                    ->label('Ganti Toko')
                    ->icon('heroicon-o-arrow-path')
                    ->url('/store/change')
                    ->visible(fn() => auth()->user()?->isSuperAdmin())
                    ->color('primary')
                    ->sort(-0.5),
                'logout' => MenuItem::make()->label('Log out')->color('danger'),
            ])
            ->topbar(!request()->is('pos*'))
            ->navigation(!request()->is('pos*'))
            ->sidebarCollapsibleOnDesktop()
            ->font('poppins')
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ]);
    }
}
