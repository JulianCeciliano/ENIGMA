<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CipherPageController;
use App\Http\Controllers\HistoryPageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'auth/terminal-access')->name('home');

Route::middleware(['guest'])->group(function (): void {
    Route::get('/terminal-access', [AuthController::class, 'create'])->name('terminal-access');
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.attempt');
});

Route::middleware(['auth'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::prefix('ciphers')->name('ciphers.')->group(function (): void {
        Route::get('/catalog', [CipherPageController::class, 'catalog'])->name('catalog');
        Route::get('/encrypt', [CipherPageController::class, 'encrypt'])->name('encrypt');
        Route::get('/decrypt', [CipherPageController::class, 'decrypt'])->name('decrypt');
        Route::get('/break', [CipherPageController::class, 'breakPage'])->name('break');
    });

    Route::get('/history', [HistoryPageController::class, 'index'])->name('history');
});
