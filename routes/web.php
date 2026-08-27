<?php

use App\Http\Controllers\BackupsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\CostaRicaPeopleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->middleware('auth')->name('home');
Route::get('users', [UsersController::class, 'index'])->middleware('auth')->name('users.index');
Route::get('clients', [ClientsController::class, 'index'])->middleware('auth')->name('clients.index');
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
Route::get('api/people/{cedula}', [CostaRicaPeopleController::class, 'show'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('api.people.show');
