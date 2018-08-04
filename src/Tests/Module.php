<?php
/**
 * Based on Herloct's Slim 3.0 Connector
 * https://github.com/herloct/codeception-slim-module
 */

namespace App\Tests;

use Codeception\Configuration;
use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\DoctrineProvider;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Container\ContainerInterface;
use Slim\App;

class Module extends Framework implements DoctrineProvider
{
    protected $requiredFields = ['container'];

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var App
     */
    public $app;

    /**
     * @var EntityManagerInterface
     */
    public $em;

    public function _initialize()
    {
        if (!defined('APP_TESTING_MODE')) {
            define('APP_TESTING_MODE', true);
        }

        $cwd = getcwd();
        chdir(Configuration::projectDir());
        $this->container = include Configuration::projectDir() . $this->config['container'];
        chdir($cwd);

        $this->app = $this->container->get('app');
        $this->em = $this->container[EntityManager::class];

        parent::_initialize();
    }

    public function _before(TestInterface $test)
    {
        $this->client = new Connector();
        $this->client->setApp($this->app);

        parent::_before($test);
    }

    public function _after(TestInterface $test)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        parent::_after($test);
    }

    /**
     * @return EntityManagerInterface
     */
    public function _getEntityManager()
    {
        return $this->em;
    }
}