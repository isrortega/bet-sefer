<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Staff\ReaderManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', ['name' => 'Bet-Sefer']);
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::get('/email/verify/{user}/{hash}', [VerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.dashboard');
    Route::get('/account/history', [AccountController::class, 'history'])->name('account.history');
    Route::get('/account/card', [AccountController::class, 'card'])->name('account.card');
});

Route::middleware(['auth'])->prefix('/staff')->name('staff.')->group(function () {
    Route::get('/readers', [ReaderManagementController::class, 'index'])->name('readers.index');
    Route::post('/readers/{user}/verify', [ReaderManagementController::class, 'verify'])->name('readers.verify');
    Route::post('/readers/{ulid}/restore', [ReaderManagementController::class, 'restore'])->name('readers.restore');
    Route::delete('/readers/{user}', [ReaderManagementController::class, 'destroy'])->name('readers.destroy');
});
