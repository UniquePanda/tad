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

class StoreShowControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ARTIST_ID = '4aBcDeFgHiJkLmNoPqRsTu';

    private function connectedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        SpotifyAuth::create([
            'user_id' => $user->id,
            'refresh_token' => 'refresh',
            'access_token' => 'access-token',
            'access_token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    public function test_adds_show_by_url_and_seeds_baseline(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID.'/albums*' => Http::response([
                'items' => [[
                    'id' => 'album_1',
                    'name' => 'Episode 1',
                    'release_date' => '2024-05-17',
                    'release_date_precision' => 'day',
                    'images' => [],
                ]],
                'next' => null,
            ]),
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID => Http::response([
                'id' => self::ARTIST_ID,
                'name' => 'Some Series',
                'images' => [['url' => 'https://i.scdn.co/image/artist']],
            ]),
        ]);
        $user = $this->connectedUser();

        $response = $this->actingAs($user)->post(route('shows.store'), [
            'artist' => 'https://open.spotify.com/artist/'.self::ARTIST_ID.'?si=xyz',
        ]);

        $response->assertRedirect(route('shows.index'));
        $response->assertSessionHas('status', WatchlistStatus::ShowAdded->value);

        $show = $user->shows()->sole();
        $this->assertSame(self::ARTIST_ID, $show->spotify_artist_id);
        $this->assertSame('Some Series', $show->name);
        $this->assertSame('https://i.scdn.co/image/artist', $show->image_url);
        $this->assertSame(ShowSource::Manual, $show->source);
        $this->assertNotNull($show->last_checked_at);
        $this->assertSame(1, $show->knownReleases()->count());
        $this->assertSame(0, $show->knownReleases()->unseen()->count());
    }

    public function test_keeps_show_when_baseline_sync_fails(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID.'/albums*' => Http::response([], 500),
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID => Http::response(['id' => self::ARTIST_ID, 'name' => 'Some Series']),
        ]);
        $user = $this->connectedUser();

        $response = $this->actingAs($user)->post(route('shows.store'), ['artist' => self::ARTIST_ID]);

        $response->assertRedirect(route('shows.index'));
        $this->assertNull($user->shows()->sole()->last_checked_at);
    }

    public function test_rejects_unparseable_input(): void
    {
        Http::fake();

        $response = $this->actingAs($this->connectedUser())
            ->from(route('shows.index'))
            ->post(route('shows.store'), ['artist' => 'not a spotify link']);

        $response->assertRedirect(route('shows.index'));
        $response->assertSessionHasErrors(['artist' => __('watchlist.invalid_artist')]);
        $this->assertDatabaseCount('shows', 0);
        Http::assertNothingSent();
    }

    public function test_requires_artist(): void
    {
        $this->actingAs($this->connectedUser())
            ->post(route('shows.store'), ['artist' => ''])
            ->assertSessionHasErrors('artist');
    }

    public function test_rejects_show_already_on_watchlist(): void
    {
        Http::fake();
        $user = $this->connectedUser();
        Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => self::ARTIST_ID,
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);

        $response = $this->actingAs($user)->post(route('shows.store'), ['artist' => 'spotify:artist:'.self::ARTIST_ID]);

        $response->assertSessionHasErrors(['artist' => __('watchlist.already_watched')]);
        $this->assertDatabaseCount('shows', 1);
        Http::assertNothingSent();
    }

    public function test_rejects_unknown_artist(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/*' => Http::response(['error' => ['status' => 404]], 404),
        ]);

        $response = $this->actingAs($this->connectedUser())->post(route('shows.store'), ['artist' => self::ARTIST_ID]);

        $response->assertSessionHasErrors(['artist' => __('watchlist.artist_not_found')]);
        $this->assertDatabaseCount('shows', 0);
    }

    public function test_reports_spotify_server_errors(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/*' => Http::response([], 503),
        ]);

        $response = $this->actingAs($this->connectedUser())->post(route('shows.store'), ['artist' => self::ARTIST_ID]);

        $response->assertSessionHasErrors(['artist' => __('watchlist.spotify_error')]);
        $this->assertDatabaseCount('shows', 0);
    }

    public function test_requires_spotify_connection(): void
    {
        Http::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('shows.store'), ['artist' => self::ARTIST_ID]);

        $response->assertSessionHasErrors(['artist' => __('watchlist.spotify_not_connected')]);
        $this->assertDatabaseCount('shows', 0);
        Http::assertNothingSent();
    }

    public function test_guest_cannot_add_show(): void
    {
        $this->post(route('shows.store'), ['artist' => self::ARTIST_ID])->assertRedirect(route('login'));
    }
}
