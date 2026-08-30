<?php

use App\Http\Controllers\Api\V1\Public\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('v1/public/health', HealthController::class)
    ->middleware('throttle:api-public')
    ->name('api.v1.public.health');
