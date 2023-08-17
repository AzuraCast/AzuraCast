<?php

declare(strict_types=1);

namespace App\Container;

use DI\Attribute\Inject;
use Monolog\Logger;

trait LoggerAwareTrait
{
    protected Logger $logger;

    #[Inject]
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
