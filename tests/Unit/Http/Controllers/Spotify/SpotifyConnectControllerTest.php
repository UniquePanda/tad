<?php

namespace Tests\Unit\Http\Controllers\Spotify;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotifyConnectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_redirects_to_spotify_and_stores_state(): void
    {
        config()->set('services.spotify.client_id', 'client-123');
        config()->set('services.spotify.redirect', 'http://127.0.0.1:8000/spotify/callback');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('spotify.connect'));

        $response->assertSessionHas('spotify_oauth_state');
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.spotify.com/authorize?', $location);
        $this->assertStringContainsString('client_id=client-123', $location);
        $this->assertStringContainsString('response_type=code', $location);
    }

    public function test_guest_cannot_connect(): void
    {
        $this->get(route('spotify.connect'))->assertRedirect(route('login'));
    }
}
