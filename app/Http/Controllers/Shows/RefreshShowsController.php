<?php

namespace App\Http\Controllers\Shows;

use App\Enums\WatchlistStatus;
use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Http\Controllers\Controller;
use App\Services\ReleaseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefreshShowsController extends Controller
{
    /**
     * Check all shows of the user for new releases right now.
     */
    public function __invoke(Request $request, ReleaseSyncService $sync): RedirectResponse
    {
        try {
            $newReleaseCount = $sync->syncUser($request->user());
        } catch (SpotifyReauthorizationRequiredException) {
            return redirect()->route('shows.index')->with('status', WatchlistStatus::RefreshFailed->value);
        }

        return redirect()->route('shows.index')
            ->with('status', WatchlistStatus::Refreshed->value)
            ->with('newReleaseCount', $newReleaseCount);
    }
}
