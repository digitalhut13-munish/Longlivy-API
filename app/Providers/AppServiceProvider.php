<?php

namespace App\Providers;

use App\Contracts\Integrations\BarcodeLookupService;
use App\Contracts\Integrations\HealthPlatformAdapter;
use App\Contracts\Integrations\MealRecognitionService;
use App\Contracts\Integrations\PushNotificationService;
use App\Foundation\Http\ApiResponseFactory;
use App\Services\Integrations\AiMealRecognitionService;
use App\Services\Integrations\OpenFoodFactsBarcodeLookupService;
use App\Services\Integrations\StubBarcodeLookupService;
use App\Services\Integrations\StubHealthPlatformAdapter;
use App\Services\Integrations\StubMealRecognitionService;
use App\Services\Integrations\StubPushNotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
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
        if (config('longlivy.integrations.barcode_driver') === 'stub') {
            $this->app->bind(
                BarcodeLookupService::class,
                StubBarcodeLookupService::class
            );
        } else {
            $this->app->bind(
                BarcodeLookupService::class,
                OpenFoodFactsBarcodeLookupService::class
            );
        }

        if (config('longlivy.integrations.recognition_driver') === 'stub') {
            $this->app->bind(
                MealRecognitionService::class,
                StubMealRecognitionService::class
            );
        } else {
            $this->app->bind(
                MealRecognitionService::class,
                AiMealRecognitionService::class
            );
        }

        $this->app->bind(PushNotificationService::class, StubPushNotificationService::class);
        $this->app->bind(
            HealthPlatformAdapter::class,
            fn ($app) => new StubHealthPlatformAdapter('stub')
        );

        $this->app->singleton(
            ResponseFactoryContract::class,
            fn ($app) => new ApiResponseFactory(
                $app['view'],
                $app['redirect']
            )
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(
                'auth:'.$request->input('email', '')
                .':'.$request->ip()
            );
        });

        // Password reset codes: every attempt answers in the same way,
        // but the endpoint stays throttled to avoid mail flooding.
        RateLimiter::for('auth.forgot', function (Request $request) {
            return Limit::perMinutes(15, 3)->by(
                'auth:forgot:'.$request->input('email', '')
                .':'.$request->ip()
            );
        });

        // Account deletion requires the current password; 5 tries per
        // minute stops brute-forcing it.
        RateLimiter::for('auth.delete', function (Request $request) {
            return Limit::perMinute(5)->by(
                'auth:delete:'.$request->ip()
            );
        });

        // Email verification codes: one send per minute per user.
        RateLimiter::for('auth.verify', function (Request $request) {
            return Limit::perMinute(1)->by(
                'auth:verify:'.$request->user()?->id ?: $request->ip()
            );
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                'api:'.$request->user()?->id ?: 'ip:'.$request->ip()
            );
        });

        // Playback progress heartbeats: at most 10 per minute per
        // running session, independently of the global API limit.
        RateLimiter::for('meditation.progress', function (Request $request) {
            $userId = $request->user()?->id ?: 'guest';
            $sessionId = $request->route('session') ?? 'session';

            return Limit::perMinute(10)->by(
                'meditation:progress:'.$userId.':'.$sessionId
            );
        });

        // AI meal recognition (photo / voice / text): 20 drafts per
        // hour per user.
        RateLimiter::for('nutrition.recognize', function (Request $request) {
            $userId = $request->user()?->id ?: 'guest';

            return Limit::perHour(20)->by(
                'nutrition:recognize:'.$userId
            );
        });
    }
}
