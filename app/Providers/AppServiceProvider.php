<?php

namespace App\Providers;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\View\PanelsIconAlias;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => view('icons.panel-left-close'),
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => view('icons.panel-left-close'),
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => view('icons.panel-left-open'),
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => view('icons.panel-left-open'),
        ]);

        Select::configureUsing(fn (Select $select): Select => $select->native(false));
        SelectFilter::configureUsing(fn (SelectFilter $filter): SelectFilter => $filter->native(false));
        SelectColumn::configureUsing(fn (SelectColumn $column): SelectColumn => $column->native(false));

        RateLimiter::for('tenant-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                Str::lower($request->string('email')->toString()).'|'.$request->ip(),
            );
        });

        View::composer('layouts.tenant', function ($view): void {
            $view->with([
                'businessName' => Setting::get('business_name', 'Kost'),
                'unreadNotificationCount' => auth()->user()?->unreadNotifications()->count() ?? 0,
            ]);
        });
    }
}
