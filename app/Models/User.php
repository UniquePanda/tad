<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'is_admin',
        'locale',
        'spotify_source_playlist_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The first user created in the system automatically becomes an admin.
        static::creating(function (User $user): void {
            if ($user->is_admin === null) {
                $user->is_admin = static::count() === 0;
            }
        });
    }

    /**
     * Only admins may access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * The name Filament displays for this user (we use the username).
     */
    public function getFilamentName(): string
    {
        return $this->username;
    }

    /**
     * @return HasOne<SpotifyAuth, $this>
     */
    public function spotifyAuth(): HasOne
    {
        return $this->hasOne(SpotifyAuth::class);
    }

    /**
     * @return HasMany<Show, $this>
     */
    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    /**
     * @return HasMany<PlaybackProgress, $this>
     */
    public function playbackProgress(): HasMany
    {
        return $this->hasMany(PlaybackProgress::class);
    }

    /**
     * @return HasMany<Suggestion, $this>
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }
}
