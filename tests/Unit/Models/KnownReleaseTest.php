<?php

namespace Tests\Unit\Models;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\KnownRelease;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KnownReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function makeRelease(array $overrides = []): KnownRelease
    {
        $show = Show::create([
            'user_id' => User::factory()->create()->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);

        return $show->knownReleases()->create(array_merge([
            'spotify_album_id' => 'album_1',
            'name' => 'Episode 1',
            'release_date' => '2024-05',
            'release_date_precision' => ReleaseDatePrecision::Month,
            'total_tracks' => 12,
            'notified_at' => now(),
        ], $overrides));
    }

    public function test_release_date_precision_is_cast_to_enum(): void
    {
        $this->assertSame(ReleaseDatePrecision::Month, $this->makeRelease()->fresh()->release_date_precision);
    }

    public function test_release_date_is_kept_as_raw_string(): void
    {
        $this->assertSame('2024-05', $this->makeRelease()->fresh()->release_date);
    }

    public function test_total_tracks_is_cast_to_integer(): void
    {
        $this->assertSame(12, $this->makeRelease()->fresh()->total_tracks);
    }

    public function test_notified_at_is_cast_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->makeRelease()->fresh()->notified_at);
    }

    public function test_show_relation(): void
    {
        $release = $this->makeRelease();
        $this->assertInstanceOf(Show::class, $release->show);
        $this->assertSame($release->show_id, $release->show->id);
    }
}
