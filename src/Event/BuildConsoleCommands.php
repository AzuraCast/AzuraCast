<?php

declare(strict_types=1);

namespace App\Event;

use App\Console\Application;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildConsoleCommands extends Event
{
    private array $aliases = [];

    public function __construct(
        private readonly Application $cli,
        private readonly ContainerInterface $di
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
