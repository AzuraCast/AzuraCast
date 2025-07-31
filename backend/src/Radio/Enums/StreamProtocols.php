<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'string')]
enum StreamProtocols: string
{
    case Icy = 'icy';
    case Http = 'http';
    case Https = 'https';
}
