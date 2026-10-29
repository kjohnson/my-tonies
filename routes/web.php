<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'wishlist'])->name('wishlist');
Route::get('/already-have', [PublicController::class, 'alreadyHave'])->name('already-have');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/wishlist/{tonie}', [AdminController::class, 'toggle'])->name('admin.toggle');
    Route::post('/admin/sync', [AdminController::class, 'sync'])->name('admin.sync');
});
