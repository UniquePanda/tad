<?php

namespace App\Enums;

enum ShowSource: string
{
    case Playlist = 'playlist';
    case Manual = 'manual';
    case Suggestion = 'suggestion';
}
