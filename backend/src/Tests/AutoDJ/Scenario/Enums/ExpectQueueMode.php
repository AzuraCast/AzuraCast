<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario\Enums;

enum ExpectQueueMode: string
{
    case Exact = 'exact';
    case Membership = 'membership';
    case None = 'none';
}
