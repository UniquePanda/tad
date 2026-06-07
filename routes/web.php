<?php

use App\Http\Controllers\Spotify\SpotifyCallbackController;
use App\Http\Controllers\Spotify\SpotifyConnectController;
use App\Http\Controllers\Spotify\SpotifyDisconnectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard', [
            'status' => session('status'),
        ]);
    })->name('dashboard');

    Route::get('spotify/connect', SpotifyConnectController::class)->name('spotify.connect');
    Route::get('spotify/callback', SpotifyCallbackController::class)->name('spotify.callback');
    Route::delete('spotify/disconnect', SpotifyDisconnectController::class)->name('spotify.disconnect');
});

require __DIR__.'/auth.php';
