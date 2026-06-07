<?php

namespace Tests\Unit\Http\Controllers\Spotify;

use App\Enums\SpotifyConnectionStatus;
use App\Models\SpotifyAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotifyDisconnectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_disconnect_removes_stored_authorization(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        SpotifyAuth::create([
            'user_id' => $user->id,
            'refresh_token' => 'refresh',
            'access_token' => 'access',
            'access_token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)->delete(route('spotify.disconnect'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', SpotifyConnectionStatus::Disconnected->value);
        $this->assertDatabaseCount('spotify_auth', 0);
    }

    public function test_guest_cannot_disconnect(): void
    {
        $this->delete(route('spotify.disconnect'))->assertRedirect(route('login'));
    }
}
