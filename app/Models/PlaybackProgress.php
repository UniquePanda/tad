<?php

namespace App\Models;

use Database\Factories\PlaybackProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackProgress extends Model
{
    /** @use HasFactory<PlaybackProgressFactory> */
    use HasFactory;

    protected $table = 'playback_progress';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'show_id',
        'spotify_album_id',
        'context_uri',
        'track_uri',
        'track_name',
        'position_ms',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_ms' => 'integer',
            'duration_ms' => 'integer',
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
     * @return BelongsTo<Show, $this>
     */
    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }
}
