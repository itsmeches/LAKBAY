<?php

namespace App\Providers\Filament;

use App\Filament\Ched\Pages\Overview;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ChedPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('ched')->path('ched')->brandName('LAKBAY • CHED IV-A')->login()->passwordReset()->profile()->authGuard('web')
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->colors(['primary' => Color::Blue])
            ->discoverResources(in: app_path('Filament/Ched/Resources'), for: 'App\\Filament\\Ched\\Resources')
            ->discoverPages(in: app_path('Filament/Ched/Pages'), for: 'App\\Filament\\Ched\\Pages')
            ->homeUrl(fn (): string => Overview::getUrl())
            ->navigationGroups(['Review & correspondence', 'Monitoring & support', 'Activity records', 'Activity reports', 'Reports', 'Restricted cases', 'Administration'])
            ->renderHook('panels::head.end', fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/lakbay-workspace.css').'">'))
            ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AuthenticateSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class, SubstituteBindings::class, DisableBladeIconComponents::class, DispatchServingFilamentEvent::class])
            ->authMiddleware([Authenticate::class]);
    }
}
