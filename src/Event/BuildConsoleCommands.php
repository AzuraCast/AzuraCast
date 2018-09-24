<?php
namespace App\Event;

use App\Console\Application;
use Symfony\Component\EventDispatcher\Event;

class BuildConsoleCommands extends Event
{
    const NAME = 'build-console-commands';

    /** @var Application */
    protected $cli;

    public function __construct(Application $cli)
    {
        $this->cli = $cli;
    }

    public function getConsole(): Application
    {
        return $this->cli;
    }
}
