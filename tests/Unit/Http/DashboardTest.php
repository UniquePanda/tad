<?php

namespace Tests\Unit\Http;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_passes_unseen_release_count(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $show = Show::create([
            'user_id' => $user->id,
            'spotify_artist_id' => 'artist_1',
            'name' => 'Some Series',
            'source' => ShowSource::Manual,
        ]);
        foreach (['album_1' => null, 'album_2' => now()] as $albumId => $seenAt) {
            $show->knownReleases()->create([
                'spotify_album_id' => $albumId,
                'name' => 'Episode',
                'release_date' => '2024',
                'release_date_precision' => ReleaseDatePrecision::Year,
                'seen_at' => $seenAt,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('unseenReleaseCount', 1)
        );
    }
}
