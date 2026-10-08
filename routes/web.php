<?php

use App\Http\Controllers\BillingKwitansiController;
use App\Http\Controllers\TunggakanPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/billing/kwitansi/{payment}', BillingKwitansiController::class)
        ->name('billing.kwitansi');

    Route::get('/billing/daftar-tunggakan', TunggakanPdfController::class)
        ->name('billing.daftar-tunggakan');
});
