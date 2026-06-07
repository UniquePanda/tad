<?php

namespace App\Services;

use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\SpotifyAuth;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SpotifyService
{
    private const AUTHORIZE_URL = 'https://accounts.spotify.com/authorize';

    private const TOKEN_URL = 'https://accounts.spotify.com/api/token';

    private const ME_URL = 'https://api.spotify.com/v1/me';

    /**
     * Seconds before expiry at which an access token is treated as stale.
     */
    private const EXPIRY_LEEWAY = 60;

    /**
     * Generate a random state value for CSRF protection of the OAuth flow.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Build the Spotify authorize URL (standard Authorization Code flow).
     */
    public function buildAuthorizeUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'scope' => implode(' ', config('services.spotify.scopes')),
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL.'?'.$query;
    }

    /**
     * Exchange an authorization code for tokens and persist them for the user.
     */
    public function exchangeCodeForToken(User $user, string $code): SpotifyAuth
    {
        $token = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
            ])
            ->throw()
            ->json();

        $auth = SpotifyAuth::updateOrCreate(
            ['user_id' => $user->id],
            [
                'refresh_token' => $token['refresh_token'],
                'access_token' => $token['access_token'],
                'access_token_expires_at' => Carbon::now()->addSeconds($token['expires_in']),
                'scope' => $token['scope'] ?? null,
            ],
        );

        $auth->spotify_user_id = $this->fetchSpotifyUserId($token['access_token']);
        $auth->save();

        return $auth;
    }

    /**
     * Refresh the access token. Persists a rotated refresh token if Spotify returns one.
     */
    public function refresh(SpotifyAuth $auth): SpotifyAuth
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $auth->refresh_token,
            ]);

        // A 400 invalid_grant means the refresh token is permanently dead (user
        // revoked access, client secret reset, ...). Drop the stored auth and
        // signal that the user must reconnect, instead of failing every call.
        if ($response->clientError() && $response->json('error') === 'invalid_grant') {
            $auth->delete();

            throw new SpotifyReauthorizationRequiredException;
        }

        // Other failures (5xx, network) are transient — let them propagate.
        $token = $response->throw()->json();

        $auth->access_token = $token['access_token'];
        $auth->access_token_expires_at = Carbon::now()->addSeconds($token['expires_in']);

        if (! empty($token['scope'])) {
            $auth->scope = $token['scope'];
        }

        // The standard flow usually keeps the same refresh token, but Spotify may
        // rotate it; persist the new value whenever one is returned.
        if (! empty($token['refresh_token'])) {
            $auth->refresh_token = $token['refresh_token'];
        }

        $auth->save();

        return $auth;
    }

    /**
     * Return a valid access token for the user, refreshing on demand.
     */
    public function validAccessTokenFor(User $user): ?string
    {
        $auth = $user->spotifyAuth;

        if ($auth === null) {
            return null;
        }

        if ($this->isExpired($auth)) {
            try {
                $auth = $this->refresh($auth);
            } catch (SpotifyReauthorizationRequiredException) {
                // Token is gone; treat the user as no longer connected.
                return null;
            }
        }

        return $auth->access_token;
    }

    private function isExpired(SpotifyAuth $auth): bool
    {
        return $auth->access_token === null
            || $auth->access_token_expires_at === null
            || $auth->access_token_expires_at->subSeconds(self::EXPIRY_LEEWAY)->isPast();
    }

    private function fetchSpotifyUserId(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)->get(self::ME_URL);

        return $response->successful() ? $response->json('id') : null;
    }

    private function clientId(): string
    {
        return (string) config('services.spotify.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.spotify.client_secret');
    }

    private function redirectUri(): string
    {
        return (string) config('services.spotify.redirect');
    }
}
