<?php

namespace App\Http\Controllers\Spotify;

use App\Enums\SpotifyConnectionStatus;
use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SpotifyCallbackController extends Controller
{
    /**
     * Handle the OAuth callback from Spotify and store the tokens.
     */
    public function __invoke(Request $request, SpotifyService $spotify): RedirectResponse
    {
        $expectedState = $request->session()->pull('spotify_oauth_state');

        // Reject denied authorizations and any state mismatch (CSRF protection).
        if ($request->filled('error')
            || ! $request->filled('code')
            || $expectedState === null
            || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('dashboard')->with('status', SpotifyConnectionStatus::Failed->value);
        }

        try {
            $spotify->exchangeCodeForToken($request->user(), (string) $request->query('code'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('dashboard')->with('status', SpotifyConnectionStatus::Failed->value);
        }

        return redirect()->route('dashboard')->with('status', SpotifyConnectionStatus::Connected->value);
    }
}
