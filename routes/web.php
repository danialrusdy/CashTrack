<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Auth::routes(['register' => false, 'reset' => false]);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard-data', [DashboardController::class, 'getData'])->name('dashboard.data');

    Route::get('/transactions',         [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create',  [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions',        [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transactions/export',  [TransactionController::class, 'export'])->name('transactions.export');
});
