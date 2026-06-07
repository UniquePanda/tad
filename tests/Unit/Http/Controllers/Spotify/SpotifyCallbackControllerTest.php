<?php

namespace Tests\Unit\Http\Controllers\Spotify;

use App\Enums\SpotifyConnectionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpotifyCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_exchanges_code_and_stores_tokens(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'access-abc',
                'refresh_token' => 'refresh-xyz',
                'expires_in' => 3600,
                'scope' => 'user-read-playback-state',
            ]),
            'https://api.spotify.com/v1/me' => Http::response(['id' => 'spotify-user-1']),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['spotify_oauth_state' => 'state-123'])
            ->get(route('spotify.callback', ['code' => 'auth-code', 'state' => 'state-123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', SpotifyConnectionStatus::Connected->value);

        $this->assertDatabaseHas('spotify_auth', [
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-user-1',
        ]);

        $auth = $user->spotifyAuth;
        $this->assertSame('access-abc', $auth->access_token);
        $this->assertSame('refresh-xyz', $auth->refresh_token);
    }

    public function test_callback_rejects_state_mismatch_without_calling_spotify(): void
    {
        Http::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['spotify_oauth_state' => 'expected-state'])
            ->get(route('spotify.callback', ['code' => 'auth-code', 'state' => 'attacker-state']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', SpotifyConnectionStatus::Failed->value);
        $this->assertDatabaseCount('spotify_auth', 0);
        Http::assertNothingSent();
    }

    public function test_callback_handles_user_denial(): void
    {
        Http::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['spotify_oauth_state' => 'state-123'])
            ->get(route('spotify.callback', ['error' => 'access_denied', 'state' => 'state-123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', SpotifyConnectionStatus::Failed->value);
        $this->assertDatabaseCount('spotify_auth', 0);
        Http::assertNothingSent();
    }

    public function test_guest_cannot_use_callback(): void
    {
        $this->get(route('spotify.callback'))->assertRedirect(route('login'));
    }
}
