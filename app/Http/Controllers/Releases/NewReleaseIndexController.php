<?php

namespace App\Http\Controllers\Releases;

use App\Http\Controllers\Controller;
use App\Models\KnownRelease;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewReleaseIndexController extends Controller
{
    /**
     * List the user's unseen (new) releases, newest first.
     */
    public function __invoke(Request $request): Response
    {
        $releases = $request->user()->knownReleases()
            ->unseen()
            ->with('show:id,name')
            ->orderByDesc('known_releases.release_date')
            ->orderBy('known_releases.name')
            ->get()
            ->map(fn (KnownRelease $release) => [
                'id' => $release->id,
                'spotify_album_id' => $release->spotify_album_id,
                'name' => $release->name,
                'image_url' => $release->image_url,
                'release_date' => $release->release_date,
                'release_date_precision' => $release->release_date_precision->value,
                'show' => [
                    'id' => $release->show->id,
                    'name' => $release->show->name,
                ],
            ]);

        return Inertia::render('releases/Index', [
            'releases' => $releases,
        ]);
    }
}
