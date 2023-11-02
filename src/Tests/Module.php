<?php

/**
 * Based on Herloct's Slim 3.0 Connector
 * https://github.com/herloct/codeception-slim-module
 */

declare(strict_types=1);

namespace App\Tests;

use App\AppFactory;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Enums\ApplicationEnvironment;
use App\Environment;
use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\DoctrineProvider;
use Codeception\Lib\ModuleContainer;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Slim\App;

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
class Module extends Framework implements DoctrineProvider
{
    public ContainerInterface $container;

    public App $app;

    public ReloadableEntityManagerInterface $em;

    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        parent::__construct($moduleContainer, $config);

        $this->requiredFields = ['container'];
    }

    public function _initialize(): void
    {
        $this->app = AppFactory::createApp(
            [
                Environment::APP_ENV => ApplicationEnvironment::Testing->value,
            ]
        );

        $container = $this->app->getContainer();
        if (null === $container) {
            throw new RuntimeException('Container was not set on App.');
        }

        $this->container = $container;
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
