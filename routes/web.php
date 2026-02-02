<?php

use App\Http\Controllers\TransactionPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app');
})->name('home');

// Route untuk print transaksi

// Route print default (thermal)
Route::get('/transaction/{id}/print', [TransactionPrintController::class, 'print'])
    ->name('transaction.print')
    ->middleware(['auth']);

// Route print dengan format tertentu
// Format yang didukung: thermal, a4, dotmatrix (atau lq310)
Route::get('/transaction/{id}/print/{format}', [TransactionPrintController::class, 'printWithFormat'])
    ->name('transaction.print.format')
    ->where('format', 'thermal|a4|dotmatrix|lq310')
    ->middleware(['auth']);

// Route preview print (opsional)
Route::get('/transaction/{id}/preview/{format?}', [TransactionPrintController::class, 'preview'])
    ->name('transaction.print.preview')
    ->middleware(['auth']);
