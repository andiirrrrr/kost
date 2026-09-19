<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\AdminLogin;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\Profile;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName(fn (): string => Setting::get('business_name', 'Sistem Manajemen Kost'))
            ->sidebarWidth('17rem')
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4.5rem')
            ->maxContentWidth(Width::Full)
            ->darkMode(false)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="preconnect" href="https://fonts.googleapis.com">'
                    .'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                    .'<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">'
                ),
            )
            ->login(AdminLogin::class)
            ->profile(Profile::class, isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::hex('#031636'),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Operasional'),
                NavigationGroup::make()->label('Keuangan'),
                NavigationGroup::make()->label('Komunikasi'),
                NavigationGroup::make()->label('Pengaturan Website')->collapsed(),
                NavigationGroup::make()->label('Sistem & Data')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Profil Saya')
                    ->url(fn (): string => Profile::getUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.auth.profile'))
                    ->icon('heroicon-o-user-circle')
                    ->group('Sistem & Data')
                    ->sort(99),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
