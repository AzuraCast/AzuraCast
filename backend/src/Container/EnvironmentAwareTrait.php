<?php

declare(strict_types=1);

namespace App\Container;

use App\Environment;
use DI\Attribute\Inject;

trait EnvironmentAwareTrait
{
    protected Environment $environment;

    #[Inject]
    public function setEnvironment(Environment $environment): void
    {
        $this->environment = $environment;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
