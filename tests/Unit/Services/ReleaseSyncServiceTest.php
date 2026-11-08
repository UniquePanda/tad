<?php

namespace Tests\Unit\Services;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\KnownRelease;
use App\Models\Show;
use App\Models\SpotifyAuth;
use App\Models\User;
use App\Services\ReleaseSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReleaseSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ReleaseSyncService
    {
        return app(ReleaseSyncService::class);
    }

    private function connectedUser(): User
    {
        $user = User::factory()->create();
        SpotifyAuth::create([
            'user_id' => $user->id,
            'refresh_token' => 'refresh',
            'access_token' => 'access-token',
            'access_token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    private function makeShow(User $user, string $artistId = 'artist_1', bool $checked = false): Show
    {
        return Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => $artistId,
            'name' => 'Series '.$artistId,
            'source' => ShowSource::Manual,
            'last_checked_at' => $checked ? now()->subDay() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function album(string $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'name' => 'Episode '.$id,
            'release_date' => '2024-05-17',
            'release_date_precision' => 'day',
            'total_tracks' => 10,
            'images' => [['url' => 'https://i.scdn.co/image/'.$id]],
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $albums
     */
    private function fakeAlbums(array $albums, string $artistId = 'artist_1'): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/'.$artistId.'/albums*' => Http::response(['items' => $albums, 'next' => null]),
        ]);
    }

    public function test_first_sync_seeds_back_catalogue_as_seen(): void
    {
        $this->fakeAlbums([$this->album('album_1'), $this->album('album_2')]);
        $show = $this->makeShow($this->connectedUser());

        $newCount = $this->service()->syncShow($show);

        $this->assertSame(0, $newCount);
        $this->assertSame(2, $show->knownReleases()->count());
        $this->assertSame(0, $show->knownReleases()->unseen()->count());
        $this->assertNotNull($show->fresh()->last_checked_at);
    }

    public function test_later_sync_flags_only_new_releases_as_unseen(): void
    {
        $show = $this->makeShow($this->connectedUser(), checked: true);
        $show->knownReleases()->create([
            'spotify_album_id' => 'album_1',
            'name' => 'Episode album_1',
            'release_date' => '2024-05-17',
            'release_date_precision' => ReleaseDatePrecision::Day,
            'seen_at' => now()->subDay(),
        ]);
        $this->fakeAlbums([$this->album('album_1'), $this->album('album_2'), $this->album('album_3')]);

        $newCount = $this->service()->syncShow($show);

        $this->assertSame(2, $newCount);
        $this->assertEqualsCanonicalizing(['album_2', 'album_3'], $show->knownReleases()->unseen()->pluck('spotify_album_id')->all());
    }

    public function test_sync_never_resets_seen_at_of_existing_rows(): void
    {
        $show = $this->makeShow($this->connectedUser(), checked: true);
        $seen = $show->knownReleases()->create([
            'spotify_album_id' => 'album_1',
            'name' => 'Episode album_1',
            'release_date' => '2024-05-17',
            'release_date_precision' => ReleaseDatePrecision::Day,
            'seen_at' => now()->subWeek(),
        ]);
        $unseen = $show->knownReleases()->create([
            'spotify_album_id' => 'album_2',
            'name' => 'Episode album_2',
            'release_date' => '2024-05-17',
            'release_date_precision' => ReleaseDatePrecision::Day,
        ]);
        $this->fakeAlbums([$this->album('album_1'), $this->album('album_2')]);

        $newCount = $this->service()->syncShow($show);

        $this->assertSame(0, $newCount);
        $this->assertTrue($seen->seen_at->equalTo($seen->fresh()->seen_at));
        $this->assertNull($unseen->fresh()->seen_at);
    }

    public function test_sync_refreshes_cached_metadata(): void
    {
        $show = $this->makeShow($this->connectedUser(), checked: true);
        $release = $show->knownReleases()->create([
            'spotify_album_id' => 'album_1',
            'name' => 'Old name',
            'release_date' => '2024',
            'release_date_precision' => ReleaseDatePrecision::Year,
            'image_url' => 'https://i.scdn.co/image/old',
            'seen_at' => now(),
        ]);
        $this->fakeAlbums([$this->album('album_1', ['name' => 'New name', 'total_tracks' => 7])]);

        $this->service()->syncShow($show);

        $release->refresh();
        $this->assertSame('New name', $release->name);
        $this->assertSame('2024-05-17', $release->release_date);
        $this->assertSame(ReleaseDatePrecision::Day, $release->release_date_precision);
        $this->assertSame(7, $release->total_tracks);
        $this->assertSame('https://i.scdn.co/image/album_1', $release->image_url);
    }

    public function test_sync_respects_release_date_precision(): void
    {
        $this->fakeAlbums([
            $this->album('album_year', ['release_date' => '2019', 'release_date_precision' => 'year']),
            $this->album('album_month', ['release_date' => '2020-03', 'release_date_precision' => 'month']),
            $this->album('album_day', ['release_date' => '2021-04-05', 'release_date_precision' => 'day']),
        ]);
        $show = $this->makeShow($this->connectedUser());

        $this->service()->syncShow($show);

        $releases = $show->knownReleases()->get()->keyBy('spotify_album_id');
        $this->assertSame('2019', $releases['album_year']->release_date);
        $this->assertSame(ReleaseDatePrecision::Year, $releases['album_year']->release_date_precision);
        $this->assertSame('2020-03', $releases['album_month']->release_date);
        $this->assertSame(ReleaseDatePrecision::Month, $releases['album_month']->release_date_precision);
        $this->assertSame(ReleaseDatePrecision::Day, $releases['album_day']->release_date_precision);
    }

    public function test_sync_infers_missing_precision_from_date_format(): void
    {
        $this->fakeAlbums([
            $this->album('album_year', ['release_date' => '2019', 'release_date_precision' => null]),
            $this->album('album_month', ['release_date' => '2020-03', 'release_date_precision' => null]),
        ]);
        $show = $this->makeShow($this->connectedUser());

        $this->service()->syncShow($show);

        $releases = $show->knownReleases()->get()->keyBy('spotify_album_id');
        $this->assertSame(ReleaseDatePrecision::Year, $releases['album_year']->release_date_precision);
        $this->assertSame(ReleaseDatePrecision::Month, $releases['album_month']->release_date_precision);
    }

    public function test_sync_tolerates_duplicate_albums_in_response(): void
    {
        $show = $this->makeShow($this->connectedUser(), checked: true);
        $this->fakeAlbums([$this->album('album_1'), $this->album('album_1')]);

        $newCount = $this->service()->syncShow($show);

        $this->assertSame(1, $newCount);
        $this->assertSame(1, $show->knownReleases()->count());
    }

    public function test_sync_user_syncs_all_shows_and_sums_new_releases(): void
    {
        $user = $this->connectedUser();
        $this->makeShow($user, 'artist_1', checked: true);
        $this->makeShow($user, 'artist_2', checked: true);
        $this->makeShow(User::factory()->create(), 'artist_3', checked: true);

        Http::fake([
            'https://api.spotify.com/v1/artists/artist_1/albums*' => Http::response(['items' => [$this->album('a1')], 'next' => null]),
            'https://api.spotify.com/v1/artists/artist_2/albums*' => Http::response(['items' => [$this->album('a2'), $this->album('a3')], 'next' => null]),
        ]);

        $this->assertSame(3, $this->service()->syncUser($user));
        Http::assertSentCount(2);
        $this->assertSame(3, KnownRelease::count());
    }

    public function test_sync_user_skips_failing_show(): void
    {
        $user = $this->connectedUser();
        $this->makeShow($user, 'artist_1', checked: true);
        $working = $this->makeShow($user, 'artist_2', checked: true);

        Http::fake([
            'https://api.spotify.com/v1/artists/artist_1/albums*' => Http::response(['error' => ['status' => 404]], 404),
            'https://api.spotify.com/v1/artists/artist_2/albums*' => Http::response(['items' => [$this->album('a2')], 'next' => null]),
        ]);

        $this->assertSame(1, $this->service()->syncUser($user));
        $this->assertSame(1, $working->knownReleases()->count());
    }

    public function test_sync_user_aborts_when_spotify_is_not_connected(): void
    {
        $user = User::factory()->create();
        $this->makeShow($user);
        Http::fake();

        $this->assertThrows(
            fn () => $this->service()->syncUser($user),
            SpotifyReauthorizationRequiredException::class,
        );
        Http::assertNothingSent();
    }
}
