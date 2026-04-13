<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\TeamController;

// ==========================================
// GUEST ROUTES (Only accessible if NOT logged in)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// ==========================================
// AUTHENTICATED ROUTES (Must be logged in to access)
// ==========================================
Route::middleware('auth')->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ==========================================
    // FINANCE MODULE
    // ==========================================
    Route::prefix('finance')->group(function () {
        
        Route::get('/loans/{type?}', [FinanceController::class, 'index'])->name('finance.loans');
        Route::get('/overview/{type?}', [FinanceController::class, 'index'])->name('finance.index');
        
        Route::post('/loans', [FinanceController::class, 'store'])->name('finance.store');
        Route::get('/loan/{id}', [FinanceController::class, 'show'])->name('finance.show');
        Route::post('/payment', [FinanceController::class, 'addPayment'])->name('finance.payment.store');
        
        Route::get('/export/{type?}', [FinanceController::class, 'export'])->name('finance.export');
        Route::delete('/payment/{id}', [FinanceController::class, 'deletePayment'])->name('finance.payment.destroy');
        
        // NEW: Route to delete an entire loan
        Route::delete('/loan/{id}', [FinanceController::class, 'destroyLoan'])->name('finance.destroy');
        Route::put('/loan/{id}/actual-months', [FinanceController::class, 'updateActualMonths'])->name('finance.update_actual_months');
    });

    // ==========================================
    // TEAM / USER MANAGEMENT MODULE
    // ==========================================
    Route::prefix('team')->group(function () {
        
        Route::get('/members', [TeamController::class, 'index'])->name('team.index');
        Route::get('/create', [TeamController::class, 'create'])->name('team.create'); 
        Route::post('/store', [TeamController::class, 'store'])->name('team.store');
        //Route::get('/show/{id}', [TeamController::class, 'show'])->name('team.show');
        Route::put('/update/{id}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('/destroy/{id}', [TeamController::class, 'destroy'])->name('team.destroy');
        
        // Logs Route
        Route::get('/logs', [TeamController::class, 'logs'])->name('team.logs'); 
        
    });
});