<?php

namespace App\Http\Controllers\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpotifyConnectController extends Controller
{
    /**
     * Redirect the user to Spotify to authorize the application.
     */
    public function __invoke(Request $request, SpotifyService $spotify): RedirectResponse
    {
        $state = $spotify->generateState();

        $request->session()->put('spotify_oauth_state', $state);

        return redirect()->away($spotify->buildAuthorizeUrl($state));
    }
}
