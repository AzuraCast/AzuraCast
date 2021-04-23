<?php

namespace App\Event;

use App\Console\Application;
use Symfony\Contracts\EventDispatcher\Event;

class BuildConsoleCommands extends Event
{
    public function __construct(
        protected Application $cli
    ) {
    }

    public function getConsole(): Application
    {
        return $this->cli;
    }
}
