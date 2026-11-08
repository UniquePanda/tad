<?php

namespace Tests\Unit\Http\Controllers\Shows;

use App\Enums\ShowSource;
use App\Enums\WatchlistStatus;
use App\Models\Show;
use App\Models\SpotifyAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefreshShowsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_shows_and_flashes_new_release_count(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/artist_1/albums*' => Http::response([
                'items' => [[
                    'id' => 'album_new',
                    'name' => 'Episode 2',
                    'release_date' => '2026-11-01',
                    'release_date_precision' => 'day',
                    'images' => [],
                ]],
                'next' => null,
            ]),
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        SpotifyAuth::create([
            'user_id' => $user->id,
            'refresh_token' => 'refresh',
            'access_token' => 'access-token',
            'access_token_expires_at' => now()->addHour(),
        ]);
        $show = Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
            'last_checked_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->post(route('shows.refresh'));

        $response->assertRedirect(route('shows.index'));
        $response->assertSessionHas('status', WatchlistStatus::Refreshed->value);
        $response->assertSessionHas('newReleaseCount', 1);
        $this->assertSame(1, $show->knownReleases()->unseen()->count());
    }

    public function test_flashes_failure_when_spotify_is_not_connected(): void
    {
        Http::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);

        $response = $this->actingAs($user)->post(route('shows.refresh'));

        $response->assertRedirect(route('shows.index'));
        $response->assertSessionHas('status', WatchlistStatus::RefreshFailed->value);
        Http::assertNothingSent();
    }

    public function test_guest_cannot_refresh(): void
    {
        $this->post(route('shows.refresh'))->assertRedirect(route('login'));
    }
}
