<?php

namespace App\Services;

use App\Enums\ReleaseDatePrecision;
use App\Exceptions\SpotifyReauthorizationRequiredException;
use App\Models\Show;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseSyncService
{
    public function __construct(private readonly SpotifyApiClient $api) {}

    /**
     * Diff the artist's albums against the show's known releases and return the
     * number of genuinely new releases.
     *
     * On a show's first sync the whole back catalogue is stored as already seen
     * (baseline), so only releases appearing afterwards count as new.
     *
     * @throws SpotifyReauthorizationRequiredException
     * @throws RequestException
     */
    public function syncShow(Show $show): int
    {
        $albums = $this->api->getArtistAlbums($show->user, $show->spotify_artist_id);

        $isBaseline = $show->last_checked_at === null;
        $now = Carbon::now();

        return DB::transaction(function () use ($show, $albums, $isBaseline, $now): int {
            $known = $show->knownReleases()->get()->keyBy('spotify_album_id');
            $newCount = 0;

            foreach ($albums as $album) {
                $attributes = $this->releaseAttributes($album);
                $release = $known->get($album['id']);

                if ($release !== null) {
                    // Refresh the cached metadata, but never touch seen_at.
                    $release->update($attributes);

                    continue;
                }

                // Remember the new row so a duplicate within the same API
                // response is treated as known instead of violating the unique key.
                $known->put($album['id'], $show->knownReleases()->create([
                    ...$attributes,
                    'spotify_album_id' => $album['id'],
                    'seen_at' => $isBaseline ? $now : null,
                ]));

                if (! $isBaseline) {
                    $newCount++;
                }
            }

            $show->update(['last_checked_at' => $now]);

            return $newCount;
        });
    }

    /**
     * Sync every show of the user and return the total number of new releases.
     *
     * A failing show (e.g. an artist removed from Spotify) is reported and
     * skipped so it cannot block the others; a revoked Spotify authorization
     * aborts the whole run.
     *
     * @throws SpotifyReauthorizationRequiredException
     */
    public function syncUser(User $user): int
    {
        $newCount = 0;

        foreach ($user->shows as $show) {
            try {
                $newCount += $this->syncShow($show);
            } catch (RequestException $e) {
                report($e);
            }
        }

        return $newCount;
    }

    /**
     * Map a Spotify album object to the cached release metadata.
     *
     * @param  array<string, mixed>  $album
     * @return array<string, mixed>
     */
    private function releaseAttributes(array $album): array
    {
        $releaseDate = (string) $album['release_date'];

        return [
            'name' => $album['name'],
            'release_date' => $releaseDate,
            'release_date_precision' => ReleaseDatePrecision::tryFrom($album['release_date_precision'] ?? '')
                ?? $this->inferPrecision($releaseDate),
            'total_tracks' => $album['total_tracks'] ?? null,
            'image_url' => $album['images'][0]['url'] ?? null,
        ];
    }

    /**
     * Fallback when Spotify omits the precision: derive it from the date format
     * (`2024`, `2024-05` or `2024-05-17`).
     */
    private function inferPrecision(string $releaseDate): ReleaseDatePrecision
    {
        return match (strlen($releaseDate)) {
            4 => ReleaseDatePrecision::Year,
            7 => ReleaseDatePrecision::Month,
            default => ReleaseDatePrecision::Day,
        };
    }
}
