<?php

declare(strict_types=1);

namespace App\Event;

use App\Console\Application;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildConsoleCommands extends Event
{
    protected array $aliases = [];

    public function __construct(
        protected Application $cli,
        protected ContainerInterface $di
    ) {
    }

    public function getConsole(): Application
    {
        return $this->cli;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->di;
    }

    public function addAliases(array $aliases): void
    {
        $this->aliases = array_merge($this->aliases, $aliases);
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }
}
