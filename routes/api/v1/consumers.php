<?php

use App\Http\Controllers\Api\V1\Consumers\CostaRicaPeopleController;
use App\Http\Controllers\Api\V1\Consumers\ExchangeRatesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api-consumers'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('exchange-rates/latest', [ExchangeRatesController::class, 'latest'])
            ->name('exchange-rates.latest');
        Route::get('exchange-rates/{date}', [ExchangeRatesController::class, 'show'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->name('exchange-rates.show');
    });

Route::middleware(['auth:sanctum', 'throttle:api-consumers'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('people/{cedula}', [CostaRicaPeopleController::class, 'show'])
            ->name('people.show');
    });
