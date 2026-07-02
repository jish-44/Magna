<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Magna\Auth\Http\Controllers\EmailVerificationController;
use Magna\Auth\Http\Controllers\ForgotPasswordController;
use Magna\Auth\Http\Controllers\LoginController;
use Magna\Auth\Http\Controllers\LogoutController;
use Magna\Auth\Http\Controllers\RegisterController;
use Magna\Auth\Http\Controllers\ResetPasswordController;
use Magna\Auth\Http\Controllers\TwoFactorChallengeController;
use Magna\Auth\Http\Controllers\TwoFactorSetupController;

/*
|--------------------------------------------------------------------------
| Magna Auth — Web (session) routes
|--------------------------------------------------------------------------
| Prefix: /auth  (set in AuthServiceProvider)
| Security headers are applied by the SecurityHeadersMiddleware added to
| the web group in bootstrap/app.php; CSP is added for admin paths in Stage 10.
*/

// Guest-only routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showForm'])->name('auth.login');
    Route::post('/login', [LoginController::class, 'attempt'])->name('auth.login.attempt');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('auth.register');
    Route::post('/register', [RegisterController::class, 'store'])->name('auth.register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    // 2FA challenge (reached after password auth, before session is fully established)
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'showForm'])
        ->name('auth.two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])
        ->name('auth.two-factor.challenge.verify');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('auth.logout');

    // Email verification
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // 2FA management (JSON API-style, consumed by admin UI in Stage 10)
    Route::post('/two-factor/enrol', [TwoFactorSetupController::class, 'enrol'])
        ->name('auth.two-factor.enrol');
    Route::post('/two-factor/confirm', [TwoFactorSetupController::class, 'confirm'])
        ->name('auth.two-factor.confirm');
    Route::delete('/two-factor', [TwoFactorSetupController::class, 'disable'])
        ->name('auth.two-factor.disable');
    Route::get('/two-factor/recovery-codes', [TwoFactorSetupController::class, 'recoveryCodes'])
        ->name('auth.two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes', [TwoFactorSetupController::class, 'regenerateRecoveryCodes'])
        ->name('auth.two-factor.recovery-codes.regenerate');
});
