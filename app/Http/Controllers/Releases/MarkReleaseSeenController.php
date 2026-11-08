<?php

namespace App\Http\Controllers\Releases;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarkReleaseSeenController extends Controller
{
    /**
     * Mark a single release of the user as seen.
     */
    public function __invoke(Request $request, int $release): RedirectResponse
    {
        $request->user()->knownReleases()->findOrFail($release)->update(['seen_at' => now()]);

        return redirect()->route('releases.index');
    }
}
