<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\Mitra\AvailabilityController;
use App\Http\Controllers\Api\Mitra\ProfileController;
use App\Http\Controllers\Api\Mitra\ServiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SlotController;
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

// Katalog publik (guest boleh)
Route::get('service-categories', [CatalogController::class, 'categories']);
Route::get('services', [CatalogController::class, 'services']);
Route::get('services/{id}', [CatalogController::class, 'showService'])->whereNumber('id');
Route::get('property-types', [CatalogController::class, 'propertyTypes']);
Route::get('slots', [SlotController::class, 'index']);

// Pesanan: guest atau login (pemilik dibaca dari token kalau ada)
Route::post('orders/quote', [QuoteController::class, 'store']);
Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:20,1');
Route::post('orders/track', [OrderController::class, 'track']);
Route::get('orders/{order_code}/status-history', [OrderController::class, 'history']);

// Khusus customer login
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('customer/orders', [OrderController::class, 'myOrders']);
    Route::get('orders/{order_code}', [OrderController::class, 'show']);
});

// Khusus mitra login (P3a: profil, jasa, jadwal. Offers dan order menyusul di P3b/P3c)
Route::middleware(['auth:sanctum', 'role:mitra'])->prefix('mitra')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::put('services', [ServiceController::class, 'update']);

    Route::get('availabilities', [AvailabilityController::class, 'index']);
    Route::put('availabilities', [AvailabilityController::class, 'update']);
});