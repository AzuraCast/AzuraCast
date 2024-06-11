<?php

declare(strict_types=1);

namespace App\Container;

use DI\Attribute\Inject;
use DI\Container;

trait ContainerAwareTrait
{
    protected Container $di;

    #[Inject]
    public function setContainer(Container $container): void
    {
        $this->di = $container;
    }

    public function getContainer(): Container
    {
        return $this->di;
    }
}
