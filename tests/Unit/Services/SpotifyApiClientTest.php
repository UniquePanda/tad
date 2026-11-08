<?php

namespace Tests\Unit\Services;

use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\SpotifyAuth;
use App\Models\User;
use App\Services\SpotifyApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyApiClientTest extends TestCase
{
    use RefreshDatabase;

    private const ARTIST_ID = '4aBcDeFgHiJkLmNoPqRsTu';

    private function client(): SpotifyApiClient
    {
        return app(SpotifyApiClient::class);
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

    public function test_parse_artist_id_accepts_share_url(): void
    {
        $this->assertSame(self::ARTIST_ID, SpotifyApiClient::parseArtistId('https://open.spotify.com/artist/'.self::ARTIST_ID.'?si=abc123'));
    }

    public function test_parse_artist_id_accepts_localized_share_url(): void
    {
        $this->assertSame(self::ARTIST_ID, SpotifyApiClient::parseArtistId('https://open.spotify.com/intl-de/artist/'.self::ARTIST_ID));
    }

    public function test_parse_artist_id_accepts_uri(): void
    {
        $this->assertSame(self::ARTIST_ID, SpotifyApiClient::parseArtistId('spotify:artist:'.self::ARTIST_ID));
    }

    public function test_parse_artist_id_accepts_bare_id_with_whitespace(): void
    {
        $this->assertSame(self::ARTIST_ID, SpotifyApiClient::parseArtistId('  '.self::ARTIST_ID."\n"));
    }

    public function test_parse_artist_id_rejects_invalid_input(): void
    {
        $this->assertNull(SpotifyApiClient::parseArtistId('https://open.spotify.com/album/'.self::ARTIST_ID));
        $this->assertNull(SpotifyApiClient::parseArtistId('https://example.com/artist/'.self::ARTIST_ID));
        $this->assertNull(SpotifyApiClient::parseArtistId('spotify:artist:tooshort'));
        $this->assertNull(SpotifyApiClient::parseArtistId(''));
    }

    public function test_get_artist_returns_artist_with_bearer_token(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID => Http::response(['id' => self::ARTIST_ID, 'name' => 'Some Series']),
        ]);

        $artist = $this->client()->getArtist($this->connectedUser(), self::ARTIST_ID);

        $this->assertSame('Some Series', $artist['name']);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer access-token'));
    }

    public function test_get_artist_throws_on_not_found(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/artists/*' => Http::response(['error' => ['status' => 404]], 404),
        ]);

        $this->assertThrows(
            fn () => $this->client()->getArtist($this->connectedUser(), self::ARTIST_ID),
            RequestException::class,
        );
    }

    public function test_get_artist_requires_spotify_connection(): void
    {
        Http::fake();

        $this->assertThrows(
            fn () => $this->client()->getArtist(User::factory()->create(), self::ARTIST_ID),
            SpotifyReauthorizationRequiredException::class,
        );
        Http::assertNothingSent();
    }

    public function test_get_artist_albums_follows_pagination(): void
    {
        $nextUrl = 'https://api.spotify.com/v1/artists/'.self::ARTIST_ID.'/albums?offset=50&limit=50&include_groups=album,single';

        Http::fake([
            '*offset=50*' => Http::response([
                'items' => [['id' => 'album_3']],
                'next' => null,
            ]),
            'https://api.spotify.com/v1/artists/'.self::ARTIST_ID.'/albums*' => Http::response([
                'items' => [['id' => 'album_1'], ['id' => 'album_2']],
                'next' => $nextUrl,
            ]),
        ]);

        $albums = $this->client()->getArtistAlbums($this->connectedUser(), self::ARTIST_ID);

        $this->assertSame(['album_1', 'album_2', 'album_3'], array_column($albums, 'id'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request['include_groups'] === 'album,single'
            && (int) $request['limit'] === 50);
    }

    public function test_get_artist_albums_requires_spotify_connection(): void
    {
        Http::fake();

        $this->assertThrows(
            fn () => $this->client()->getArtistAlbums(User::factory()->create(), self::ARTIST_ID),
            SpotifyReauthorizationRequiredException::class,
        );
        Http::assertNothingSent();
    }
}
