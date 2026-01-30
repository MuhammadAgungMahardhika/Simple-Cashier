<?php

use App\Http\Controllers\TransactionPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');
// Route untuk print receipt
Route::get('/transaction/{id}/print', [TransactionPrintController::class, 'print'])
    ->name('transaction.print')
    ->middleware(['auth']); // Tambahkan middleware sesuai kebutuhan

// Atau jika ingin ada pilihan format print:
Route::get('/transaction/{id}/print/{format}', [TransactionPrintController::class, 'printWithFormat'])
    ->name('transaction.print.format')
    ->where('format', 'thermal|a4')
    ->middleware(['auth']);
