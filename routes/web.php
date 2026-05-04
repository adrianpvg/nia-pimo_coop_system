<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\TeamController;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Mail\SendEmail;
use Illuminate\Auth\Events\Verified;
use App\Models\User;

// ==========================================
// GUEST ROUTES (Only accessible if NOT logged in)
// ==========================================
Route::get('/', function () {
    return redirect()->route('login');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Split Register Routes
    Route::get('/register', [AuthController::class, 'registerView'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // ==========================================
    // CUSTOM EMAIL VERIFICATION (Bypasses Login Requirement)
    // ==========================================
    Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect('/login')->withErrors(['email' => 'Invalid or expired verification link.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/login')->with('success', 'Email already verified. Please wait for admin approval.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/login')->with('success', 'Email successfully verified! Your account is now pending Administrator approval.');
    })->middleware(['signed'])->name('verification.verify');
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
    
        // NEW: Route to update a payment's OR number
        Route::put('/payment/{id}', [FinanceController::class, 'updatePayment'])->name('finance.payment.update');
        
        // NEW: Route to delete an entire loan
        Route::delete('/loan/{id}', [FinanceController::class, 'destroyLoan'])->name('finance.destroy');
        Route::put('/loan/{id}/actual-months', [FinanceController::class, 'updateActualMonths'])->name('finance.update_actual_months');
        Route::get('/loan/{id}/export-sched', [FinanceController::class, 'exportSched'])->name('finance.export_sched');
    });

    // ==========================================
    // TEAM / USER MANAGEMENT MODULE
    // ==========================================
    Route::prefix('team')->group(function () {
        Route::patch('/toggle-status/{id}', [TeamController::class, 'toggleActive'])->name('team.toggle');
        
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