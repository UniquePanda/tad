<?php

namespace App\Services;

use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SpotifyApiClient
{
    private const API_URL = 'https://api.spotify.com/v1';

    /**
     * Page size for the artist albums endpoint (Spotify's maximum).
     */
    private const ALBUMS_PAGE_SIZE = 50;

    public function __construct(private readonly SpotifyService $spotify) {}

    /**
     * Extract a Spotify artist ID from a share URL, a `spotify:artist:` URI or a bare ID.
     */
    public static function parseArtistId(string $input): ?string
    {
        $input = trim($input);

        $patterns = [
            // https://open.spotify.com/artist/{id} (optionally with an intl-xx segment and query string)
            '~^https?://open\.spotify\.com/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?artist/([A-Za-z0-9]{22})(?:[/?#].*)?$~i',
            '~^spotify:artist:([A-Za-z0-9]{22})$~',
            '~^([A-Za-z0-9]{22})$~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Fetch a single artist (`GET /v1/artists/{id}`).
     *
     * @return array<string, mixed>
     *
     * @throws SpotifyReauthorizationRequiredException
     * @throws RequestException
     */
    public function getArtist(User $user, string $artistId): array
    {
        return Http::withToken($this->accessToken($user))
            ->get(self::API_URL.'/artists/'.$artistId)
            ->throw()
            ->json();
    }

    /**
     * Fetch all albums and singles of an artist, following Spotify's pagination.
     *
     * @return list<array<string, mixed>>
     *
     * @throws SpotifyReauthorizationRequiredException
     * @throws RequestException
     */
    public function getArtistAlbums(User $user, string $artistId): array
    {
        $token = $this->accessToken($user);
        $albums = [];

        $url = self::API_URL.'/artists/'.$artistId.'/albums?'.http_build_query([
            'include_groups' => 'album,single',
            'limit' => self::ALBUMS_PAGE_SIZE,
        ]);

        while ($url !== null) {
            $page = Http::withToken($token)->get($url)->throw()->json();

            array_push($albums, ...($page['items'] ?? []));

            $url = $page['next'] ?? null;
        }

        return $albums;
    }

    /**
     * @throws SpotifyReauthorizationRequiredException
     */
    private function accessToken(User $user): string
    {
        return $this->spotify->validAccessTokenFor($user)
            ?? throw new SpotifyReauthorizationRequiredException;
    }
}
