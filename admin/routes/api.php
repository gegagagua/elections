<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CustomerApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthApiController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout'])->name('api.logout');

    Route::get('/customers', [CustomerApiController::class, 'index'])->name('api.customers.index');
    Route::post('/customers/{customer}/status', [CustomerApiController::class, 'updateStatus'])->name('api.customers.status');
    Route::post('/customers/{customer}/image', [CustomerApiController::class, 'uploadImage'])->name('api.customers.image.upload');
    Route::delete('/customers/{customer}/image', [CustomerApiController::class, 'deleteImage'])->name('api.customers.image.delete');
});
