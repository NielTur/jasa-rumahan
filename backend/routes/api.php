<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Semua route di sini otomatis berawalan /api (lihat RouteServiceProvider)

Route::prefix('auth')->group(function () {
    Route::post('register/customer', [AuthController::class, 'registerCustomer']);
    Route::post('register/mitra', [AuthController::class, 'registerMitra']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});