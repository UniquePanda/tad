<?php

namespace App\Models;

use App\Enums\ReleaseDatePrecision;
use Database\Factories\KnownReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnownRelease extends Model
{
    /** @use HasFactory<KnownReleaseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'show_id',
        'spotify_album_id',
        'name',
        'release_date',
        'release_date_precision',
        'total_tracks',
        'image_url',
        'seen_at',
        'notified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_date_precision' => ReleaseDatePrecision::class,
            'total_tracks' => 'integer',
            'seen_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * Releases the user has not marked as seen yet (i.e. new episodes).
     *
     * @param  Builder<KnownRelease>  $query
     */
    #[Scope]
    protected function unseen(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn('seen_at'));
    }

    /**
     * @return BelongsTo<Show, $this>
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }
}
