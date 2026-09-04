<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\PublicPointController;
use App\Http\Controllers\Staff\DemandController;
use App\Http\Controllers\Staff\FrontDeskController;
use App\Http\Controllers\Staff\ReaderManagementController;
use App\Http\Controllers\Staff\ShelvingController;
use App\Http\Middleware\Noindex;
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
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.dashboard');
    Route::get('/account/history', [AccountController::class, 'history'])->name('account.history');
    Route::get('/account/card', [AccountController::class, 'card'])->name('account.card');
});

Route::middleware(['auth'])->prefix('/staff')->name('staff.')->group(function () {
    Route::get('/desk', [FrontDeskController::class, 'index'])->name('desk');
    Route::post('/desk/checkout', [FrontDeskController::class, 'checkout'])->name('desk.checkout');
    Route::post('/desk/checkin', [FrontDeskController::class, 'checkin'])->name('desk.checkin');
    Route::post('/desk/renew', [FrontDeskController::class, 'renew'])->name('desk.renew');

    Route::get('/shelving', [ShelvingController::class, 'index'])->name('shelving');
    Route::post('/shelving/advance', [ShelvingController::class, 'advance'])->name('shelving.advance');

    Route::get('/readers', [ReaderManagementController::class, 'index'])->name('readers.index');
    Route::post('/readers/{user}/verify', [ReaderManagementController::class, 'verify'])->name('readers.verify');
    Route::post('/readers/{ulid}/restore', [ReaderManagementController::class, 'restore'])->name('readers.restore');
    Route::delete('/readers/{user}', [ReaderManagementController::class, 'destroy'])->name('readers.destroy');

    Route::get('/demand', [DemandController::class, 'index'])->name('demand.index');
    Route::post('/demand/{demandEvent}/resolve', [DemandController::class, 'resolve'])->name('demand.resolve');
});

// Public information point (anonymous) — allow-list payloads only.
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/i/{code}', [PublicPointController::class, 'copy'])
        ->middleware(Noindex::class)
        ->name('public.copy');
    Route::get('/lookup', [PublicPointController::class, 'lookup'])->name('public.lookup');
    Route::get('/lookup/{isbn}', [PublicPointController::class, 'isbn'])->middleware('throttle:10,1')->name('public.isbn');
});
Route::post('/lookup/suggest', [PublicPointController::class, 'suggest'])
    ->middleware('throttle:5,1')
    ->name('public.suggest');
