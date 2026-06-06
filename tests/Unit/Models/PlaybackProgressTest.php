<?php

namespace Tests\Unit\Models;

use App\Enums\ShowSource;
use App\Models\PlaybackProgress;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaybackProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_and_duration_are_cast_to_integer(): void
    {
        $progress = PlaybackProgress::create([
            'user_id' => User::factory()->create()->id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => '1500',
            'duration_ms' => '600000',
        ])->fresh();

        $this->assertSame(1500, $progress->position_ms);
        $this->assertSame(600000, $progress->duration_ms);
    }

    public function test_can_be_created_without_a_show(): void
    {
        $progress = PlaybackProgress::create([
            'user_id' => User::factory()->create()->id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => 1000,
        ]);

        $this->assertNull($progress->show_id);
        $this->assertNull($progress->show);
    }

    public function test_show_relation(): void
    {
        $user = User::factory()->create();

        $show = Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);

        $progress = PlaybackProgress::create([
            'user_id' => $user->id,
            'show_id' => $show->id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => 1000,
        ]);

        $this->assertInstanceOf(Show::class, $progress->show);
        $this->assertSame($show->id, $progress->show->id);
    }

    public function test_user_relation(): void
    {
        $user = User::factory()->create();

        $progress = PlaybackProgress::create([
            'user_id' => $user->id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => 1000,
        ]);

        $this->assertInstanceOf(User::class, $progress->user);
        $this->assertSame($user->id, $progress->user->id);
    }
}
