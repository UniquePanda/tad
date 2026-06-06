<?php

namespace Tests\Unit\Models;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\KnownRelease;
use App\Models\PlaybackProgress;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeShow(): Show
    {
        return Show::create([
            'user_id' => User::factory()->create()->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Playlist,
            'last_checked_at' => now(),
        ]);
    }

    public function test_source_is_cast_to_enum(): void
    {
        $this->assertSame(ShowSource::Playlist, $this->makeShow()->fresh()->source);
    }

    public function test_last_checked_at_is_cast_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->makeShow()->fresh()->last_checked_at);
    }

    public function test_user_relation(): void
    {
        $show = $this->makeShow();

        $this->assertInstanceOf(User::class, $show->user);
        $this->assertSame($show->user_id, $show->user->id);
    }

    public function test_known_releases_relation(): void
    {
        $show = $this->makeShow();
        $release = $show->knownReleases()->create([
            'spotify_album_id' => 'album_1',
            'name' => 'Episode 1',
            'release_date' => '2024',
            'release_date_precision' => ReleaseDatePrecision::Year,
        ]);

        $this->assertInstanceOf(KnownRelease::class, $show->knownReleases->first());
        $this->assertTrue($show->knownReleases->contains($release));
    }

    public function test_playback_progress_relation(): void
    {
        $show = $this->makeShow();
        $progress = $show->playbackProgress()->create([
            'user_id' => $show->user_id,
            'spotify_album_id' => 'album_1',
            'track_uri' => 'spotify:track:abc',
            'position_ms' => 1000,
        ]);

        $this->assertInstanceOf(PlaybackProgress::class, $show->playbackProgress);
        $this->assertSame($progress->id, $show->playbackProgress->id);
    }
}
