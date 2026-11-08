<?php

namespace Tests\Unit\Http\Controllers\Releases;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\KnownRelease;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAllReleasesSeenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeRelease(User $user, string $albumId): KnownRelease
    {
        $show = Show::firstOrCreate(
            ['user_id' => $user->id, 'spotify_artist_id' => 'artist_1'],
            ['name' => 'Some Series', 'source' => ShowSource::Manual],
        );

        return $show->knownReleases()->create([
            'spotify_album_id' => $albumId,
            'name' => 'Episode',
            'release_date' => '2024',
            'release_date_precision' => ReleaseDatePrecision::Year,
        ]);
    }

    public function test_marks_all_own_releases_as_seen(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->makeRelease($user, 'album_1');
        $this->makeRelease($user, 'album_2');
        $foreign = $this->makeRelease(User::factory()->create(), 'album_1');

        $response = $this->actingAs($user)->patch(route('releases.seen-all'));

        $response->assertRedirect(route('releases.index'));
        $this->assertSame(0, $user->knownReleases()->unseen()->count());
        $this->assertNull($foreign->fresh()->seen_at);
    }

    public function test_guest_cannot_mark_all_releases(): void
    {
        $this->patch(route('releases.seen-all'))->assertRedirect(route('login'));
    }
}
