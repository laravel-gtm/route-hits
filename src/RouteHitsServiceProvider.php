<?php

namespace LaravelGtm\RouteHits;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteHitsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/route-hits.php', 'route-hits');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'route-hits');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'route-hits');
        $this->publishes([__DIR__.'/../config/route-hits.php' => config_path('route-hits.php')], 'route-hits-config');

        // The HTTP kernel re-syncs its groups to the router when resolved, so hook there.
        $this->callAfterResolving(Kernel::class, fn (Kernel $kernel) => $kernel->appendMiddlewareToGroup('web', TrackRouteHit::class));

        Route::middleware(['web', Authorize::class])
            ->prefix(config('route-hits.path'))
            ->group(__DIR__.'/../routes/web.php');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('model:prune', ['--model' => [RouteHit::class]])->daily();
        });
    }
}
