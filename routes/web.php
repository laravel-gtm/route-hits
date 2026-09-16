<?php

use Illuminate\Support\Facades\Route;
use LaravelGtm\RouteHits\DashboardController;

Route::get('/route-hits.css', [DashboardController::class, 'css'])->name('route-hits.css');
Route::get('/', [DashboardController::class, 'index'])->name('route-hits.index');
Route::get('/{app}', [DashboardController::class, 'show'])->name('route-hits.show');
