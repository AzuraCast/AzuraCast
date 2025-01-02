<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;

final class LogType
{
    use HasLinks;

    public function __construct(
        public string $key,
        public string $name,
        public string $path,
        public bool $tail = false
    ) {
    }
}
