<?php

namespace Tests\Unit\Http\Controllers\Shows;

use App\Enums\ReleaseDatePrecision;
use App\Enums\ShowSource;
use App\Enums\WatchlistStatus;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyShowControllerTest extends TestCase
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

    public function test_removes_own_show_and_its_releases(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $show = $this->makeShow($user);
        $show->knownReleases()->create([
            'spotify_album_id' => 'album_1',
            'name' => 'Episode 1',
            'release_date' => '2024',
            'release_date_precision' => ReleaseDatePrecision::Year,
        ]);

        $response = $this->actingAs($user)->delete(route('shows.destroy', $show->id));

        $response->assertRedirect(route('shows.index'));
        $response->assertSessionHas('status', WatchlistStatus::ShowRemoved->value);
        $this->assertDatabaseCount('shows', 0);
        $this->assertDatabaseCount('known_releases', 0);
    }

    public function test_cannot_remove_show_of_another_user(): void
    {
        $show = $this->makeShow(User::factory()->create());
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->delete(route('shows.destroy', $show->id))->assertNotFound();

        $this->assertDatabaseHas('shows', ['id' => $show->id]);
    }

    public function test_guest_cannot_remove_show(): void
    {
        $show = $this->makeShow(User::factory()->create());

        $this->delete(route('shows.destroy', $show->id))->assertRedirect(route('login'));
        $this->assertDatabaseHas('shows', ['id' => $show->id]);
    }
}
