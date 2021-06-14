<?php

/**
 * Based on Herloct's Slim 3.0 Connector
 * https://github.com/herloct/codeception-slim-module
 */

namespace App\Tests;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Environment;
use Codeception\Configuration;
use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\DoctrineProvider;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

class Module extends Framework implements DoctrineProvider
{
    public ContainerInterface $container;

    public App $app;

    public ReloadableEntityManagerInterface $em;

    protected $requiredFields = ['container'];

    public function _initialize(): void
    {
        /** @var string $container_class The fully qualified name of the container class. */
        $container_class = $this->config['container'];

        $autoloader = $GLOBALS['autoloader'];

        $this->app = $container_class::createApp(
            $autoloader,
            [
                Environment::BASE_DIR => Configuration::projectDir(),
                Environment::APP_ENV => Environment::ENV_TESTING,
            ]
        );

        $this->container = $this->app->getContainer();
        $this->em = $this->container->get(ReloadableEntityManagerInterface::class);

        parent::_initialize();
    }

    public function _before(TestInterface $test): void
    {
        $this->client = new Connector();
        $this->client->setApp($this->app);

        parent::_before($test);
    }

    public function _after(TestInterface $test): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        parent::_after($test);
    }

    public function _getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
