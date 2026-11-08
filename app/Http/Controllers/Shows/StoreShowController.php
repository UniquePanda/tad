<?php

namespace App\Http\Controllers\Shows;

use App\Enums\ShowSource;
use App\Enums\WatchlistStatus;
use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Http\Controllers\Controller;
use App\Services\ReleaseSyncService;
use App\Services\SpotifyApiClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreShowController extends Controller
{
    /**
     * Add a show to the watchlist by its Spotify artist link, URI or ID.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request, SpotifyApiClient $api, ReleaseSyncService $sync): RedirectResponse
    {
        $request->validate([
            'artist' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $artistId = SpotifyApiClient::parseArtistId((string) $request->input('artist'));

        if ($artistId === null) {
            throw ValidationException::withMessages(['artist' => __('watchlist.invalid_artist')]);
        }

        if ($user->shows()->where('spotify_artist_id', $artistId)->exists()) {
            throw ValidationException::withMessages(['artist' => __('watchlist.already_watched')]);
        }

        try {
            $artist = $api->getArtist($user, $artistId);
        } catch (SpotifyReauthorizationRequiredException) {
            throw ValidationException::withMessages(['artist' => __('watchlist.spotify_not_connected')]);
        } catch (RequestException $e) {
            // 400 = malformed ID, 404 = no such artist; anything else is Spotify's fault.
            if (! in_array($e->response->status(), [400, 404], true)) {
                report($e);

                throw ValidationException::withMessages(['artist' => __('watchlist.spotify_error')]);
            }

            throw ValidationException::withMessages(['artist' => __('watchlist.artist_not_found')]);
        }

        $show = $user->shows()->create([
            'spotify_artist_id' => $artistId,
            'name' => $artist['name'],
            'image_url' => $artist['images'][0]['url'] ?? null,
            'source' => ShowSource::Manual,
        ]);

        // Baseline sync: store the back catalogue as seen. If it fails, the show
        // stays unchecked and the next refresh performs the baseline instead.
        try {
            $sync->syncShow($show);
        } catch (SpotifyReauthorizationRequiredException|RequestException $e) {
            report($e);
        }

        return redirect()->route('shows.index')->with('status', WatchlistStatus::ShowAdded->value);
    }
}
