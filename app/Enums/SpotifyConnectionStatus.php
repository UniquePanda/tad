<?php

namespace App\Enums;

enum SpotifyConnectionStatus: string
{
    case Connected = 'spotify-connected';
    case Disconnected = 'spotify-disconnected';
    case Failed = 'spotify-connect-failed';
}
