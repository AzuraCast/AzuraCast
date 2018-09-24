<?php
namespace App\Console;

use App\Event\BuildConsoleCommands;
use App\EventDispatcher;
use Slim\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{
    /** @var Container */
    protected $di;

    /**
     * @param Container $di
     */
    public function setContainer(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->di;
    }

    /**
     * @param $service_name
     * @return mixed
     * @throws \App\Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getService($service_name)
    {
        if ($this->di->has($service_name)) {
            return $this->di->get($service_name);
        } else {
            throw new \App\Exception(sprintf('Service "%s" not found.', $service_name));
        }
    }

    /**
     * @inheritdoc
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->di[EventDispatcher::class];

        // Trigger an event for the core app and all plugins to build their CLI commands.
        $event = new BuildConsoleCommands($this);
        $dispatcher->dispatch(BuildConsoleCommands::NAME, $event);

        return parent::run($input, $output);
    }
}
