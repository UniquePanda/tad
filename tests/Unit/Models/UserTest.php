<?php

namespace Tests\Unit\Models;

use App\Enums\ShowSource;
use App\Models\PlaybackProgress;
use App\Models\Show;
use App\Models\SpotifyAuth;
use App\Models\Suggestion;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_spotify_auth_relation(): void
    {
        $user = User::factory()->create();
        $auth = SpotifyAuth::create([
            'user_id' => $user->id,
            'refresh_token' => 'secret-refresh',
        ]);

        $this->assertInstanceOf(SpotifyAuth::class, $user->spotifyAuth);
        $this->assertSame($auth->id, $user->spotifyAuth->id);
    }

    public function test_shows_relation(): void
    {
        $user = User::factory()->create();
        $show = Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);

        $this->assertTrue($user->shows->contains($show));
    }

    public function test_playback_progress_relation(): void
    {
        $user = User::factory()->create();
        $progress = PlaybackProgress::create([
            'user_id' => $user->id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => 1000,
        ]);

        $this->assertTrue($user->playbackProgress->contains($progress));
    }

    public function test_suggestions_relation(): void
    {
        $user = User::factory()->create();
        $suggestion = Suggestion::create([
            'user_id' => $user->id,
            'name' => 'Maybe a series',
        ]);

        $this->assertTrue($user->suggestions->contains($suggestion));
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertNotSame('plain-password', $user->password);
        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    public function test_is_admin_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_admin' => 1]);

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_first_created_user_becomes_admin_and_others_do_not(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertTrue($first->is_admin);
        $this->assertFalse($second->is_admin);
    }

    public function test_an_explicit_is_admin_value_is_respected(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($user->is_admin);
    }

    public function test_only_admins_can_access_filament_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $this->assertTrue($admin->canAccessPanel(Panel::make()));
        $this->assertFalse($nonAdmin->canAccessPanel(Panel::make()));
    }

    public function test_filament_name_uses_username(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $this->assertSame('testuser', $user->getFilamentName());
    }

    public function test_locale_defaults_to_english(): void
    {
        $user = User::factory()->create();

        $this->assertSame('en', $user->fresh()->locale);
    }
}
