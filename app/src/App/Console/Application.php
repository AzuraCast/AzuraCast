<?php
namespace App\Console;

use Slim\Container;

/**
 * Class Application
 * Wraps the default Symfony console application with a DI-aware wrapper.
 *
 * @package App\Console
 */
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
}