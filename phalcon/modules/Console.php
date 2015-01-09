<?php

namespace Baseapp;

/**
 * Console
 *
 * @package     base-app
 * @category    CLI
 * @version     2.0
 */
class Console extends \Phalcon\CLI\Console
{

    private $_di;
    private $_config;

    /**
     * Console constructor - set the dependency Injector
     *
     * @package     base-app
     * @version     2.0
     *
     * @param \Phalcon\DiInterface $di
     */
    public function __construct(\Phalcon\DiInterface $di)
    {
        $this->_di = $di;

        $loaders = array('config', 'loader', 'assets', 'db', 'router');

        // Register services
        foreach ($loaders as $service) {
            $this->$service();
        }

        // Register modules
        $this->registerModules(array(
            'cli' => array(
                'className' => 'Baseapp\Cli\Module',
                'path' => ROOT_PATH . '/app/cli/Module.php'
            ),
        ));

        // Register the app itself as a service
        $this->_di->set('app', $this);

        // Set the dependency Injector
        parent::__construct($this->_di);
    }

    /**
     * Register an autoloader
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    protected function loader()
    {
        $loader = new \Phalcon\Loader();
        $loader->registerNamespaces(array(
            'Baseapp\Models' => ROOT_PATH . '/app/common/models/',
            'Baseapp\Library' => ROOT_PATH . '/app/common/library/',
            'Baseapp\Extension' => ROOT_PATH . '/app/common/extension/'
        ))->register();
    }

    /**
     * Set the config service
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    protected function config()
    {
        $config = new \Phalcon\Config\Adapter\Ini(ROOT_PATH . '/app/common/config/config.ini');
        $this->_di->set('config', $config);
        $this->_config = $config;
    }

    /** Set the assets service
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    protected function assets()
    {
        $this->_di->set('assets', function() {
            $assets = new \Phalcon\Assets\Manager();
            return $assets;
        });
    }

    /**
     * Set the database service
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    protected function db()
    {
        $config = $this->_config;
        $this->_di->set('db', function() use ($config) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                "host" => $config->database->host,
                "username" => $config->database->username,
                "password" => $config->database->password,
                "dbname" => $config->database->dbname,
                "options" => array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            ));
        });
    }

    /**
     * Set the static router service
     *
     * @package     base-app
     * @version     2.0
     *
     * @return void
     */
    protected function router()
    {
        $this->_di->set('router', function() {
            $router = new \Phalcon\CLI\Router();
            return $router;
        });
    }

    /**
     * HMVC request in the cli
     *
     * @package     base-app
     * @version     2.0
     *
     * @param array $location location to run the request
     *
     * @return mixed response
     */
    public function request($location)
    {
        $dispatcher = clone $this->getDI()->get('dispatcher');

        if (isset($location['task'])) {
            $dispatcher->setTaskName($location['task']);
        } else {
            $dispatcher->setTaskName('main');
        }

        if (isset($location['action'])) {
            $dispatcher->setActionName($location['action']);
        } else {
            $dispatcher->setActionName('main');
        }

        if (isset($location['params'])) {
            if (is_array($location['params'])) {
                $dispatcher->setParams($location['params']);
            } else {
                $dispatcher->setParams((array) $location['params']);
            }
        } else {
            $dispatcher->setParams(array());
        }

        $dispatcher->dispatch();
        return $dispatcher->getReturnedValue();
    }

    /**
     * Handle the command-line arguments
     *
     * @package     base-app
     * @version     2.0
     *
     * @param mixed $arguments
     */
    public function handle($arguments = null)
    {
        $params = array();
        switch (count($arguments)) {
            case 1:
                $task = 'main';
                $action = 'main';
                break;
            case 2:
                $task = $arguments[1];
                $action = 'main';
                break;
            case 3:
                $task = $arguments[1];
                $action = $arguments[2];
                break;
            default:
                $task = $arguments[1];
                $action = $arguments[2];
                $params = array_slice($arguments, 3);
                break;
        }
        $arguments = array_merge(array('module' => 'cli', 'task' => $task, 'action' => $action), $params);
        parent::handle($arguments);
    }

    /**
     * Catch the exception and log it
     *
     * @package     base-app
     * @version     2.0
     *
     * @param \Exception $e
     */
    public static function exception(\Exception $e)
    {
        $config = \Phalcon\DI::getDefault()->getShared('config');
        $errors = array(
            'error' => get_class($e) . '[' . $e->getCode() . ']: ' . $e->getMessage(),
            'info' => $e->getFile() . '[' . $e->getLine() . ']',
            'debug' => "Trace: \n" . $e->getTraceAsString() . "\n",
        );

        if ($config->app->env == "development") {
            // Display debug output
            print_r($e);
        } else {
            // Log errors to file and send email with errors to admin
            \Baseapp\Bootstrap::log($errors);
        }
    }

}
