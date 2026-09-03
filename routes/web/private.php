<?php

use App\Http\Controllers\ApiConsumersController;
use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WorkItemsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->middleware('auth')->name('home');
Route::get('users', [UsersController::class, 'index'])->middleware('auth')->name('users.index');
Route::get('clients', [ClientsController::class, 'index'])->middleware('auth')->name('clients.index');
Route::get('work-items', [WorkItemsController::class, 'index'])->middleware('auth')->name('work-items.index');
Route::get('clients/{clientCode}/backups', [BackupsController::class, 'client'])->middleware('auth')->name('clients.backups');
Route::get('clients/{clientCode}', [ClientsController::class, 'show'])->middleware('auth')->name('clients.show');
Route::get('clients/{clientCode}/backups/{backup}/download', [BackupsController::class, 'downloadClientBackup'])
    ->middleware('auth')
    ->name('clients.backups.download');
Route::get('backups', [BackupsController::class, 'index'])->middleware('auth')->name('backups.index');
Route::get('backups/download/{path}', [BackupsController::class, 'download'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('backups.download');
Route::get('users/{user}/edit', [UsersController::class, 'edit'])->middleware('auth')->name('users.edit');
Route::put('users/{user}', [UsersController::class, 'update'])->middleware('auth')->name('users.update');
Route::get('profile', [ProfileController::class, 'index'])->middleware('auth')->name('profile');
Route::post('profile/avatar', [ProfileController::class, 'regenerateAvatar'])
    ->middleware('auth')
    ->name('profile.avatar');
Route::get('integrations', [IntegrationsController::class, 'index'])->middleware('auth')->name('integrations.index');
Route::post('integrations/sync', [IntegrationsController::class, 'sync'])->middleware('auth')->name('integrations.sync');
Route::get('integrations/api-consumers', [ApiConsumersController::class, 'index'])
    ->middleware('auth')
    ->name('api-consumers.index');
Route::get('apis/exchange-rates', [ApiDocumentationController::class, 'exchangeRates'])
    ->middleware('auth')
    ->name('api-docs.exchange-rates');
