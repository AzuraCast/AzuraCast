<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario\Enums;

enum ScenarioMode: string
{
    case InMemory = 'in_memory';
    case Integration = 'integration';
}
