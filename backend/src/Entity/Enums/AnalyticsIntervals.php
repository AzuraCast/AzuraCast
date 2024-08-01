<?php

declare(strict_types=1);

namespace App\Entity\Enums;

enum AnalyticsIntervals: string
{
    case Daily = 'day';
    case Hourly = 'hour';
}
