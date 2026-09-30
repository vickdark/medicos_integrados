<?php

namespace App\Providers;

use App\Actions\Branding\BrandPalette;
use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureBranding();
    }

    /**
     * Only the administrator changes the accent color, and the documents that are
     * rendered outside the browser receive it as ready-made colors.
     */
    protected function configureBranding(): void
    {
        Gate::define('manage-branding', fn (User $user): bool => $user->hasRole(UserRole::Admin));
        Gate::define('manage-cie10', fn (User $user): bool => $user->hasRole(UserRole::Admin));
        Gate::define('view-reports', fn (User $user): bool => $user->hasRole(UserRole::Admin, UserRole::Receptionist)
            || ($user->hasRole(UserRole::Doctor) && $user->doctor !== null));

        View::composer(['pdf.*', 'exports.*'], function ($view): void {
            $view->with(BrandPalette::documentColors(AppSetting::brandColor()));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
