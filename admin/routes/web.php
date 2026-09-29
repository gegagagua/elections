<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\ManagerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/districts');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('districts', DistrictController::class);
    Route::resource('managers', ManagerController::class)->except(['show']);

    Route::prefix('districts/{district}')->name('districts.')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('customers/{customer}/status', [CustomerController::class, 'updateStatus'])->name('customers.status');
        Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
    });
});
