<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Placeholder dashboard route — replaced by the Filament admin panel in Stage 10.
Route::get('/dashboard', function () {
    return response()->json(['message' => 'Magna CMS — authenticated.']);
})->middleware('auth')->name('dashboard');
