<?php

declare(strict_types=1);

namespace App\Radio\Enums;

enum StreamProtocols: string
{
    case Icy = 'icy';
    case Http = 'http';
    case Https = 'https';
}
