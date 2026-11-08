<?php

namespace Tests\Unit\Http\Controllers\Shows;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Enums\WatchlistStatus;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_own_shows_with_unseen_release_count(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $show = Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'image_url' => 'https://i.scdn.co/image/artist',
            'source' => ShowSource::Manual,
            'last_checked_at' => now(),
        ]);
        foreach (['album_1' => null, 'album_2' => null, 'album_3' => now()] as $albumId => $seenAt) {
            $show->knownReleases()->create([
                'spotify_album_id' => $albumId,
                'name' => 'Episode',
                'release_date' => '2024',
                'release_date_precision' => ReleaseDatePrecision::Year,
                'seen_at' => $seenAt,
            ]);
        }
        Show::create([
            'user_id' => User::factory()->create()->id,
            'spotify_artist_id' => 'artist_2',
            'name' => 'Foreign Series',
            'source' => ShowSource::Manual,
        ]);

        $response = $this->actingAs($user)->get(route('shows.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('shows/Index')
            ->has('shows', 1)
            ->where('shows.0.id', $show->id)
            ->where('shows.0.name', 'Some Series')
            ->where('shows.0.image_url', 'https://i.scdn.co/image/artist')
            ->where('shows.0.unseen_releases_count', 2)
            ->where('shows.0.last_checked_at', $show->last_checked_at->toIso8601String())
            ->where('status', null)
        );
    }

    public function test_passes_flashed_refresh_result(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['status' => WatchlistStatus::Refreshed->value, 'newReleaseCount' => 3])
            ->get(route('shows.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('status', WatchlistStatus::Refreshed->value)
            ->where('newReleaseCount', 3)
        );
    }

    public function test_guest_cannot_view_watchlist(): void
    {
        $this->get(route('shows.index'))->assertRedirect(route('login'));
    }
}
