<?php

namespace Tests\Unit\Services;

use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\SpotifyAuth;
use App\Models\User;
use App\Services\SpotifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SpotifyService
    {
        return app(SpotifyService::class);
    }

    public function test_build_authorize_url_contains_required_parameters(): void
    {
        config()->set('services.spotify.client_id', 'cid');
        config()->set('services.spotify.redirect', 'http://127.0.0.1:8000/spotify/callback');
        config()->set('services.spotify.scopes', ['user-read-playback-state', 'streaming']);

        $url = $this->service()->buildAuthorizeUrl('state-xyz');

        $this->assertStringStartsWith('https://accounts.spotify.com/authorize?', $url);
        $this->assertStringContainsString('client_id=cid', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=state-xyz', $url);
        $this->assertStringContainsString(urlencode('user-read-playback-state streaming'), $url);
        $this->assertStringContainsString(urlencode('http://127.0.0.1:8000/spotify/callback'), $url);
    }

    public function test_generate_state_returns_random_value(): void
    {
        $this->assertNotSame($this->service()->generateState(), $this->service()->generateState());
    }

    public function test_exchange_code_persists_tokens_and_spotify_user(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-1',
                'refresh_token' => 'refresh-1',
                'expires_in' => 3600,
                'scope' => 'streaming',
            ]),
            'https://api.spotify.com/v1/me' => Http::response(['id' => 'sp-user']),
        ]);

        $user = User::factory()->create();

        $auth = $this->service()->exchangeCodeForToken($user, 'the-code');

        $this->assertSame('access-1', $auth->access_token);
        $this->assertSame('refresh-1', $auth->refresh_token);
        $this->assertSame('sp-user', $auth->spotify_user_id);
        $this->assertSame('streaming', $auth->scope);
        $this->assertTrue($auth->access_token_expires_at->isFuture());
        $this->assertDatabaseHas('spotify_auth', ['user_id' => $user->id, 'spotify_user_id' => 'sp-user']);
    }

    public function test_refresh_rotates_refresh_token_when_present(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-2',
                'refresh_token' => 'refresh-2',
                'expires_in' => 3600,
            ]),
        ]);

        $auth = $this->makeAuth(refreshToken: 'refresh-1');

        $auth = $this->service()->refresh($auth);

        $this->assertSame('access-2', $auth->access_token);
        $this->assertSame('refresh-2', $auth->refresh_token);
    }

    public function test_refresh_keeps_existing_refresh_token_when_absent(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-3',
                'expires_in' => 3600,
            ]),
        ]);

        $auth = $this->makeAuth(refreshToken: 'keep-me');

        $auth = $this->service()->refresh($auth);

        $this->assertSame('access-3', $auth->access_token);
        $this->assertSame('keep-me', $auth->refresh_token);
    }

    public function test_valid_access_token_refreshes_when_expired(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'fresh-token',
                'expires_in' => 3600,
            ]),
        ]);

        $auth = $this->makeAuth(accessToken: 'stale', expiresAt: now()->subMinute());

        $token = $this->service()->validAccessTokenFor($auth->user->fresh());

        $this->assertSame('fresh-token', $token);
    }

    public function test_valid_access_token_returns_cached_token_when_fresh(): void
    {
        Http::fake();

        $auth = $this->makeAuth(accessToken: 'still-good', expiresAt: now()->addHour());

        $token = $this->service()->validAccessTokenFor($auth->user->fresh());

        $this->assertSame('still-good', $token);
        Http::assertNothingSent();
    }

    public function test_valid_access_token_returns_null_without_auth(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service()->validAccessTokenFor($user));
    }

    public function test_refresh_clears_auth_and_requires_reauth_when_token_revoked(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid refresh token',
            ], 400),
        ]);

        $auth = $this->makeAuth(refreshToken: 'dead-token');

        $this->assertThrows(
            fn () => $this->service()->refresh($auth),
            SpotifyReauthorizationRequiredException::class,
        );
        $this->assertDatabaseCount('spotify_auth', 0);
    }

    public function test_refresh_rethrows_and_keeps_auth_on_server_error(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([], 500),
        ]);

        $auth = $this->makeAuth(refreshToken: 'keep-me');

        $this->assertThrows(
            fn () => $this->service()->refresh($auth),
            RequestException::class,
        );
        $this->assertDatabaseHas('spotify_auth', ['id' => $auth->id]);
    }

    public function test_valid_access_token_returns_null_when_refresh_token_revoked(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $auth = $this->makeAuth(accessToken: 'stale', expiresAt: now()->subMinute());

        $token = $this->service()->validAccessTokenFor($auth->user->fresh());

        $this->assertNull($token);
        $this->assertDatabaseCount('spotify_auth', 0);
    }

    private function makeAuth(
        string $refreshToken = 'refresh-token',
        string $accessToken = 'access-token',
        ?Carbon $expiresAt = null,
    ): SpotifyAuth {
        return SpotifyAuth::create([
            'user_id' => User::factory()->create()->id,
            'refresh_token' => $refreshToken,
            'access_token' => $accessToken,
            'access_token_expires_at' => $expiresAt ?? now()->addHour(),
        ]);
    }
}
