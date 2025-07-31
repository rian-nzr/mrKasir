<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreSelectionController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\AdminStoreSelectionController;
use App\Http\Controllers\StrukController;

// Admin store selection routes (tanpa middleware tambahan)
Route::get('/admin/store-selection', [AdminStoreSelectionController::class, 'index'])->name('admin.store-selection');
Route::post('/admin/store-selection', [AdminStoreSelectionController::class, 'select'])->name('admin.store-selection.select');

// Store selection routes
Route::middleware(['auth'])->group(function () {
    Route::get('/store/select', [StoreSelectionController::class, 'index'])->name('store.select');
    Route::post('/store/select', [StoreSelectionController::class, 'select'])->name('store.select');
    Route::get('/store/not-assigned', [StoreSelectionController::class, 'notAssigned'])->name('store.not-assigned');
    
    // Store change route
    Route::get('/store/change', [StoreController::class, 'changeStore'])->name('store.change');
});

Route::get('/struk/{orderId}', [StrukController::class, 'show'])
    ->name('struk');
