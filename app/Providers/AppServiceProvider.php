<?php

namespace App\Providers;

use App\Contracts\Integrations\BarcodeLookupService;
use App\Contracts\Integrations\HealthPlatformAdapter;
use App\Contracts\Integrations\MealRecognitionService;
use App\Contracts\Integrations\PushNotificationService;
use App\Services\Integrations\StubBarcodeLookupService;
use App\Services\Integrations\StubHealthPlatformAdapter;
use App\Services\Integrations\StubMealRecognitionService;
use App\Services\Integrations\StubPushNotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BarcodeLookupService::class, StubBarcodeLookupService::class);
        $this->app->bind(MealRecognitionService::class, StubMealRecognitionService::class);
        $this->app->bind(PushNotificationService::class, StubPushNotificationService::class);
        $this->app->bind(
            HealthPlatformAdapter::class,
            fn ($app) => new StubHealthPlatformAdapter('stub')
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                'api:'.$request->user()?->id ?: 'ip:'.$request->ip()
            );
        });
    }
}
