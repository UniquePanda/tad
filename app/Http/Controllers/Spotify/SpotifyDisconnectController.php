<?php

namespace App\Http\Controllers\Spotify;

use App\Enums\SpotifyConnectionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotifyDisconnectController extends Controller
{
    /**
     * Remove the stored Spotify authorization for the user.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->user()->spotifyAuth()->delete();

        return redirect()->route('dashboard')->with('status', SpotifyConnectionStatus::Disconnected->value);
    }
}
