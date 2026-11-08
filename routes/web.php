<?php

use App\Http\Controllers\Releases\MarkAllReleasesSeenController;
use App\Http\Controllers\Releases\MarkReleaseSeenController;
use App\Http\Controllers\Releases\NewReleaseIndexController;
use App\Http\Controllers\Shows\DestroyShowController;
use App\Http\Controllers\Shows\RefreshShowsController;
use App\Http\Controllers\Shows\ShowIndexController;
use App\Http\Controllers\Shows\StoreShowController;
use App\Http\Controllers\Spotify\SpotifyCallbackController;
use App\Http\Controllers\Spotify\SpotifyConnectController;
use App\Http\Controllers\Spotify\SpotifyDisconnectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        return Inertia::render('Dashboard', [
            'status' => session('status'),
            'unseenReleaseCount' => $request->user()->knownReleases()->unseen()->count(),
        ]);
    })->name('dashboard');

    Route::get('spotify/connect', SpotifyConnectController::class)->name('spotify.connect');
    Route::get('spotify/callback', SpotifyCallbackController::class)->name('spotify.callback');
    Route::delete('spotify/disconnect', SpotifyDisconnectController::class)->name('spotify.disconnect');

    Route::get('shows', ShowIndexController::class)->name('shows.index');
    Route::post('shows', StoreShowController::class)->name('shows.store');
    Route::post('shows/refresh', RefreshShowsController::class)->name('shows.refresh');
    Route::delete('shows/{show}', DestroyShowController::class)->whereNumber('show')->name('shows.destroy');

    Route::get('releases', NewReleaseIndexController::class)->name('releases.index');
    Route::patch('releases/seen', MarkAllReleasesSeenController::class)->name('releases.seen-all');
    Route::patch('releases/{release}/seen', MarkReleaseSeenController::class)->whereNumber('release')->name('releases.seen');
});

require __DIR__.'/auth.php';
