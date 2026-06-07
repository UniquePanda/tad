<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stored Spotify refresh token is no longer valid (revoked by the
 * user, client secret reset, etc.) and the user must reconnect their account.
 */
class SpotifyReauthorizationRequiredException extends RuntimeException
{
    protected $message = 'The Spotify refresh token is no longer valid; the user must reconnect their account.';
}
