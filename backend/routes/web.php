<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PropertySizeController;
use App\Http\Controllers\Admin\PropertyTypeController;
use App\Http\Controllers\Admin\ServiceAddonController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServicePriceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Dashboard (Blade, guard web / session). Memakai Service layer yang sama dengan API (B-1).
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    // Halaman login: hanya untuk yang belum login (yang sudah login dialihkan ke HOME)
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login.submit');
    });

    // Semua halaman admin lain
    Route::middleware('admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Data master (T3b): daftar, tambah, ubah, Aktifkan/Nonaktifkan. SENGAJA tanpa rute hapus (B-12).
        $master = [
            'service-categories' => ServiceCategoryController::class,
            'services' => ServiceController::class,
            'property-types' => PropertyTypeController::class,
            'property-sizes' => PropertySizeController::class,
            'service-prices' => ServicePriceController::class,
            'service-addons' => ServiceAddonController::class,
        ];

        foreach ($master as $uri => $controller) {
            Route::get($uri, [$controller, 'index'])->name("$uri.index");
            Route::get("$uri/create", [$controller, 'create'])->name("$uri.create");
            Route::post($uri, [$controller, 'store'])->name("$uri.store");
            Route::get("$uri/{id}/edit", [$controller, 'edit'])->whereNumber('id')->name("$uri.edit");
            Route::put("$uri/{id}", [$controller, 'update'])->whereNumber('id')->name("$uri.update");
            Route::patch("$uri/{id}/toggle", [$controller, 'toggle'])->whereNumber('id')->name("$uri.toggle");
        }
    });
});