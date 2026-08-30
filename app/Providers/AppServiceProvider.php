<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use TallStackUi\Facades\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::configure()->expose(
            document: fn (Router $router, $action) => $router
                ->get('docs/api.json', $action)
                ->name('scramble.docs.document'),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        RateLimiter::for('api-public', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('api-consumers', function (Request $request): Limit {
            return Limit::perMinute(120)->by(
                $request->user()?->currentAccessToken()?->getKey() ?? $request->ip(),
            );
        });

        TallStackUi::customize()
            ->table()
            ->block('wrapper')
            ->replace('dark:ring-dark-700', 'dark:ring-gray-900')
            ->and
            ->form()
            ->block('input.base')
            ->replace('dark:placeholder-dark-400 w-full rounded-md border-0 bg-transparent py-1.5 ring-0 placeholder:text-gray-400 focus:outline-hidden focus:ring-transparent sm:text-sm sm:leading-6', 'dark:placeholder-gray-400 w-full rounded-lg border-0 bg-transparent px-3 h-10 text-sm text-gray-800 placeholder:text-gray-400 outline-none focus:ring-0 dark:text-white/90')
            ->and
            ->form()
            ->block('input.wrapper', 'flex w-full rounded-lg border border-gray-300 focus-within:border-brand-500 focus-within:ring-0 dark:border-gray-700')
            ->and
            ->form()
            ->block('input.color.base', 'text-gray-800 dark:text-white/90')
            ->and
            ->form()
            ->block('input.color.background', 'bg-white dark:bg-gray-900')
            ->and
            ->form()
            ->block('input.color.disabled', 'bg-gray-100 dark:bg-gray-900');

        TallStackUi::customize()
            ->form('textarea')
            ->block('input.base')
            ->replace('dark:placeholder-dark-400 w-full rounded-md border-0 bg-transparent py-1.5 ring-0 placeholder:text-gray-400 focus:outline-hidden focus:ring-transparent sm:text-sm sm:leading-6', 'dark:placeholder-gray-400 w-full rounded-lg border-0 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 outline-none focus:ring-0 dark:text-white/90')
            ->and
            ->form('textarea')
            ->block('input.wrapper', 'flex w-full rounded-lg border border-gray-300 focus-within:border-brand-500 focus-within:ring-0 dark:border-gray-700')
            ->and
            ->form('textarea')
            ->block('input.color.base', 'text-gray-800 dark:text-white/90')
            ->and
            ->form('textarea')
            ->block('input.color.background', 'bg-white dark:bg-gray-900')
            ->and
            ->form('textarea')
            ->block('input.color.disabled', 'bg-gray-100 dark:bg-gray-900');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

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
