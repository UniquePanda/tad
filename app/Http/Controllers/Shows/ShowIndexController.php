<?php

namespace App\Http\Controllers\Shows;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowIndexController extends Controller
{
    /**
     * Show the user's watchlist.
     */
    public function __invoke(Request $request): Response
    {
        $shows = $request->user()->shows()
            ->withCount(['knownReleases as unseen_releases_count' => fn ($query) => $query->unseen()])
            ->orderBy('name')
            ->get()
            ->map(fn (Show $show) => [
                'id' => $show->id,
                'name' => $show->name,
                'image_url' => $show->image_url,
                'last_checked_at' => $show->last_checked_at?->toIso8601String(),
                'unseen_releases_count' => $show->unseen_releases_count,
            ]);

        return Inertia::render('shows/Index', [
            'shows' => $shows,
            'status' => session('status'),
            'newReleaseCount' => session('newReleaseCount'),
        ]);
    }
}
