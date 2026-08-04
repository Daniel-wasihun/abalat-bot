<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        \Illuminate\Database\Eloquent\Builder::macro('chronological', function () {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = $this->getModel();
            if ($model instanceof \Illuminate\Database\Eloquent\Model && $model->usesTimestamps()) {
                return $this->orderByDesc($model->getUpdatedAtColumn())
                    ->orderByDesc($model->getCreatedAtColumn());
            }
            return $this->orderByDesc($model->getKeyName());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Define Rate Limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void {
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many attempts. Please try again in a minute.',
                ], 429);
            });
        });
    }
}
