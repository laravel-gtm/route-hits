<?php

namespace LaravelGtm\RouteHits\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LaravelGtm\RouteHits\RouteHitsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [RouteHitsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('route-hits.app', 'billing');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/invoices/{id}', fn () => 'ok')->name('invoices.show');
            Route::post('/invoices', fn () => 'created');
            Route::get('/missing', fn () => abort(404));
        });
    }

    protected function user(string $email = 'devon@example.com'): User
    {
        $user = new User;
        $user->id = 1;
        $user->email = $email;

        return $user;
    }
}
