<?php

use App\Http\Controllers\CostaRicaPeopleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->middleware('auth')->name('home');
Route::get('users', [UsersController::class, 'index'])->middleware('auth')->name('users.index');
Route::get('users/{user}/edit', [UsersController::class, 'edit'])->middleware('auth')->name('users.edit');
Route::put('users/{user}', [UsersController::class, 'update'])->middleware('auth')->name('users.update');
Route::get('profile', [ProfileController::class, 'index'])->middleware('auth')->name('profile');
Route::get('api/people/{cedula}', [CostaRicaPeopleController::class, 'show'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('api.people.show');
