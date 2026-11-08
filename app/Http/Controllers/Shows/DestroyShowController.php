<?php

namespace App\Http\Controllers\Shows;

use App\Enums\WatchlistStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestroyShowController extends Controller
{
    /**
     * Remove a show (and its cached releases) from the user's watchlist.
     */
    public function __invoke(Request $request, int $show): RedirectResponse
    {
        $request->user()->shows()->findOrFail($show)->delete();

        return redirect()->route('shows.index')->with('status', WatchlistStatus::ShowRemoved->value);
    }
}
