<?php

namespace App\Models;

use Database\Factories\SpotifyAuthFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyAuth extends Model
{
    /** @use HasFactory<SpotifyAuthFactory> */
    use HasFactory;

    protected $table = 'spotify_auth';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'spotify_user_id',
        'refresh_token',
        'access_token',
        'access_token_expires_at',
        'scope',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'refresh_token',
        'access_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'access_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
