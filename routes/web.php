<?php

use Illuminate\Support\Facades\Route;

// The Magna admin panel (Filament) is mounted at the root path "/".
// Guests visiting "/" are redirected to "/login" by the panel's auth
// middleware; "/login", the dashboard, and all resources are registered by
// AdminPanelProvider (src/Magna/Admin/AdminPanelProvider.php).
//
// Until installation completes, RedirectIfNotInstalled (web middleware group)
// sends all traffic to "/install" before the panel is reached.

// Named "dashboard" route kept for the Stage 2 auth controllers and their
// tests, which redirect here after login. Points at the panel home.
Route::get('/dashboard', function () {
    return redirect('/');
})->middleware('auth')->name('dashboard');
