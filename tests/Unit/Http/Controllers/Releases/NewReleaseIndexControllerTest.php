<?php

namespace Tests\Unit\Http\Controllers\Releases;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewReleaseIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeShow(User $user): Show
    {
        return Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);
    }

    public function test_lists_own_unseen_releases_newest_first(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $show = $this->makeShow($user);
        $older = $show->knownReleases()->create([
            'spotify_album_id' => 'album_old',
            'name' => 'Episode 1',
            'release_date' => '2024-03',
            'release_date_precision' => ReleaseDatePrecision::Month,
        ]);
        $newer = $show->knownReleases()->create([
            'spotify_album_id' => 'album_new',
            'name' => 'Episode 2',
            'release_date' => '2026-11-01',
            'release_date_precision' => ReleaseDatePrecision::Day,
            'image_url' => 'https://i.scdn.co/image/album_new',
        ]);
        $show->knownReleases()->create([
            'spotify_album_id' => 'album_seen',
            'name' => 'Episode 0',
            'release_date' => '2023',
            'release_date_precision' => ReleaseDatePrecision::Year,
            'seen_at' => now(),
        ]);
        $this->makeShow(User::factory()->create())->knownReleases()->create([
            'spotify_album_id' => 'album_foreign',
            'name' => 'Foreign',
            'release_date' => '2026',
            'release_date_precision' => ReleaseDatePrecision::Year,
        ]);

        $response = $this->actingAs($user)->get(route('releases.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('releases/Index')
            ->has('releases', 2)
            ->where('releases.0.id', $newer->id)
            ->where('releases.0.spotify_album_id', 'album_new')
            ->where('releases.0.name', 'Episode 2')
            ->where('releases.0.image_url', 'https://i.scdn.co/image/album_new')
            ->where('releases.0.release_date', '2026-11-01')
            ->where('releases.0.release_date_precision', 'day')
            ->where('releases.0.show', ['id' => $show->id, 'name' => 'Some Series'])
            ->where('releases.1.id', $older->id)
            ->where('releases.1.release_date_precision', 'month')
        );
    }

    public function test_guest_cannot_view_new_releases(): void
    {
        $this->get(route('releases.index'))->assertRedirect(route('login'));
    }
}
