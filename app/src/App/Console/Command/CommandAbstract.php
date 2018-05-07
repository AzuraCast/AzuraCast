<?php
namespace App\Console\Command;

use App\Console\Application;
use Symfony\Component\Console\Command\Command;

abstract class CommandAbstract extends Command
{
    /**
     * Return a Dependency Injection service.
     *
     * @param $service_name
     * @return mixed
     * @throws \App\Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function get($service_name)
    {
        /** @var Application $application */
        $application = self::getApplication();

        return $application->getService($service_name);
    }
}