<?php

namespace App\Enums;

enum SuggestionStatus: string
{
    case Open = 'open';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
