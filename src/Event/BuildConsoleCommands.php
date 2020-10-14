<?php

namespace App\Event;

use App\Console\Application;
use Symfony\Contracts\EventDispatcher\Event;

class BuildConsoleCommands extends Event
{
    protected Application $cli;

    public function __construct(Application $cli)
    {
        $this->cli = $cli;
    }

    public function getConsole(): Application
    {
        return $this->cli;
    }
}
