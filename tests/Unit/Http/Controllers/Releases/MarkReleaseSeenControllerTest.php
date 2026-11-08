<?php

namespace Tests\Unit\Http\Controllers\Releases;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\KnownRelease;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReleaseSeenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeRelease(User $user, string $albumId = 'album_1'): KnownRelease
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

    public function test_marks_own_release_as_seen(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $release = $this->makeRelease($user);
        $other = $this->makeRelease($user, 'album_2');

        $response = $this->actingAs($user)->patch(route('releases.seen', $release->id));

        $response->assertRedirect(route('releases.index'));
        $this->assertNotNull($release->fresh()->seen_at);
        $this->assertNull($other->fresh()->seen_at);
    }

    public function test_cannot_mark_release_of_another_user(): void
    {
        $release = $this->makeRelease(User::factory()->create());
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->patch(route('releases.seen', $release->id))->assertNotFound();

        $this->assertNull($release->fresh()->seen_at);
    }

    public function test_guest_cannot_mark_release(): void
    {
        $release = $this->makeRelease(User::factory()->create());

        $this->patch(route('releases.seen', $release->id))->assertRedirect(route('login'));
    }
}
