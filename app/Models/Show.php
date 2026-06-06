<?php

namespace App\Models;

use App\Enums\ShowSource;
use Database\Factories\ShowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Show extends Model
{
    /** @use HasFactory<ShowFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'spotify_artist_id',
        'series_key',
        'name',
        'image_url',
        'source',
        'last_checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ShowSource::class,
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<KnownRelease, $this>
     */
    public function knownReleases(): HasMany
    {
        return $this->hasMany(KnownRelease::class);
    }

    /**
     * @return HasOne<PlaybackProgress, $this>
     */
    public function playbackProgress(): HasOne
    {
        return $this->hasOne(PlaybackProgress::class);
    }
}
