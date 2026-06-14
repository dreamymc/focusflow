<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Workspace::class);

        \Illuminate\Support\Facades\Gate::define('workspace.pro', function (\App\Models\User $user, \App\Models\Workspace $workspace) {
            if ($workspace->subscribed('default')) {
                return true;
            }
            return $workspace->users()->count() < 3;
        });

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn() => response()->json(['message' => 'Too many attempts.'], 429));
        });
    }
}
