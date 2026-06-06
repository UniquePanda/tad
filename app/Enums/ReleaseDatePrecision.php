<?php

namespace App\Enums;

enum ReleaseDatePrecision: string
{
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
}
