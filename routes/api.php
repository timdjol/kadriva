<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/home', [CatalogController::class, 'home']);
    Route::get('/titles', [CatalogController::class, 'index']);
    Route::get('/titles/{title}', [CatalogController::class, 'show']);
    Route::get('/titles/{title}/seasons', [CatalogController::class, 'seasons']);
    Route::get('/titles/{title}/watch-options', [CatalogController::class, 'watchOptions']);
    Route::get('/search', [CatalogController::class, 'search'])->middleware('throttle:search');
    Route::get('/countries', [CatalogController::class, 'countries']);
    Route::get('/countries/detect', [CatalogController::class, 'detectCountry']);
    Route::get('/genres', [CatalogController::class, 'genres']);

    Route::prefix('auth')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    })->middleware('throttle:auth');

    Route::prefix('me')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('/favorites', [AccountController::class, 'favorites']);
        Route::post('/favorites', [AccountController::class, 'addFavorite']);
        Route::delete('/favorites', [AccountController::class, 'removeFavorite']);
        Route::get('/progress/{title}', [AccountController::class, 'progress']);
        Route::put('/progress/{title}', [AccountController::class, 'saveProgress']);
        Route::get('/history', [AccountController::class, 'history']);
        Route::put('/settings', [AccountController::class, 'settings']);
    });
});
