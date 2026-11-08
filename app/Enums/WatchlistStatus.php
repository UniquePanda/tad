<?php

namespace App\Enums;

/**
 * Flash `status` values of the watchlist. Prefixed because `status` is a shared
 * flash channel (see SpotifyConnectionStatus).
 */
enum WatchlistStatus: string
{
    case ShowAdded = 'watchlist-show-added';
    case ShowRemoved = 'watchlist-show-removed';
    case Refreshed = 'watchlist-refreshed';
    case RefreshFailed = 'watchlist-refresh-failed';
}
