<?php

use App\Http\Api\CiphersController;
use App\Http\Api\HistoryController;
use App\Http\Api\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/ciphers', [CiphersController::class, 'index'])->name('api.ciphers.index');

Route::middleware(['auth:web,api'])->group(function (): void {
    Route::get('/user', [SessionController::class, 'user'])->name('api.user');

    Route::middleware(['throttle:cipher-transform'])->group(function (): void {
        Route::post('/ciphers/transform', [CiphersController::class, 'transform'])->name('api.ciphers.transform');
        Route::post('/cifrado/transform', [CiphersController::class, 'transform'])->name('api.cifrado.transform');
    });

    Route::middleware(['throttle:cipher-break'])->group(function (): void {
        Route::post('/ciphers/{algorithm}/break', [CiphersController::class, 'breakCipher'])->name('api.ciphers.break');
        Route::post('/ciphers/{algorithm}/romper', [CiphersController::class, 'breakCipher'])->name('api.ciphers.break-es');
        Route::post('/cifrado/{algorithm}/break', [CiphersController::class, 'breakCipher'])->name('api.cifrado.break');
        Route::post('/cifrado/{algorithm}/romper', [CiphersController::class, 'breakCipher'])->name('api.cifrado.break-es');
    });

    Route::prefix('history')->name('api.history.')->group(function (): void {
        Route::get('/', [HistoryController::class, 'index'])->name('index');
        Route::delete('/', [HistoryController::class, 'clear'])->name('clear');
    });
});
