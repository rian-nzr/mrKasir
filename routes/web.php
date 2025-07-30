<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreSelectionController;
use App\Http\Controllers\StrukController;

// Store selection routes
Route::middleware(['auth'])->group(function () {
    Route::get('/store/select', [StoreSelectionController::class, 'index'])->name('store.select');
    Route::post('/store/select', [StoreSelectionController::class, 'select'])->name('store.select');
    Route::get('/store/not-assigned', [StoreSelectionController::class, 'notAssigned'])->name('store.not-assigned');
});

Route::get('/struk/{orderId}', [StrukController::class, 'show'])
    ->name('struk');
