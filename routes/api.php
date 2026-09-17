<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReleaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('collection')->group(function (): void {
    Route::get('/', [CollectionController::class, 'index']);
    Route::post('items', [CollectionController::class, 'store']);
    Route::delete('items/{item}', [CollectionController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('releases')->group(function (): void {
    Route::get('search', [ReleaseController::class, 'search']);
    Route::get('barcode/{barcode}', [ReleaseController::class, 'barcode'])
        ->where('barcode', '[0-9]+');
});
