<?php

namespace App\Http\Controllers\Releases;

use App\Http\Controllers\Controller;
use App\Models\KnownRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarkAllReleasesSeenController extends Controller
{
    /**
     * Mark all unseen releases of the user as seen.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        KnownRelease::query()
            ->whereRelation('show', 'user_id', $request->user()->id)
            ->unseen()
            ->update(['seen_at' => now()]);

        return redirect()->route('releases.index');
    }
}
