<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateChat;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

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
        $this->configureRateLimiting();
        $this->configureLivewirePersistentMiddleware();
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
            : null
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chat-login', function (Request $request): Limit {
            $chatId = strtolower((string) $request->input('chat_id', 'guest'));
            $key = $chatId.'|'.$request->ip();

            return Limit::perMinute((int) config('chat.rate_limits.login_per_minute', 10))->by($key);
        });

        RateLimiter::for('chat-message', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('chat.rate_limits.message_per_minute', 30))->by((string) $key);
        });
    }

    protected function configureLivewirePersistentMiddleware(): void
    {
        Livewire::addPersistentMiddleware([
            AuthenticateChat::class,
        ]);
    }
}
