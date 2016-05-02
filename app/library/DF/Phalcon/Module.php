<?php
namespace DF\Phalcon;

class Module implements \Phalcon\Mvc\ModuleDefinitionInterface
{
    protected $_module_class_name;
    protected $_module_dir;

    /**
     * @param $class_name
     * @param $dir
     */
    protected function setModuleInfo($class_name, $dir)
    {
        $this->_module_class_name = $class_name;
        $this->_module_dir = $dir;
    }

    /**
     * @param \Phalcon\DiInterface $di
     */
    public function registerAutoloaders(\Phalcon\DiInterface $di = null)
    {
        $loader = new \Phalcon\Loader();

        $controller_class = 'Modules\\'.$this->_module_class_name.'\Controllers';
        $loader->registerNamespaces(array(
            $controller_class => $this->_module_dir . '/controllers/',
        ));

        $loader->register();
    }

    /**
     * @param \Phalcon\DiInterface $di
     */
    public function registerServices(\Phalcon\DiInterface $di = null)
    {
        // Set up MVC dispatcher.
        $controller_class = 'Modules\\'.$this->_module_class_name.'\Controllers';

        $di['dispatcher'] = function () use ($controller_class) {

            $eventsManager = new \Phalcon\Events\Manager();

            $eventsManager->attach("dispatch:beforeDispatchLoop", function($event, $dispatcher) {

                // Set odd/even pairs as the key and value of parameters, respectively.
                $keyParams = array();
                $params = $dispatcher->getParams();

                foreach ($params as $number => $value) {
                    if ($number & 1) {
                        $keyParams[$params[$number - 1]] = urldecode($value);
                    }
                }

                $dispatcher->setParams($keyParams);

                // Detect filename in controller and convert to "format" parameter.
                $controller_name = $dispatcher->getControllerName();

                if (strstr($controller_name, '.') !== false)
                {
                    list($controller_clean, $format) = explode('.', $controller_name, 2);

                    $dispatcher->setControllerName($controller_clean);
                    $dispatcher->setParam('format', $format);
                }

                // Detect filename in action and convert to "format" parameter.
                $action_name = $dispatcher->getActionName();

                if (strstr($action_name, '.') !== false)
                {
                    list($action_clean, $format) = explode('.', $action_name, 2);

                    $dispatcher->setActionName($action_clean);
                    $dispatcher->setParam('format', $format);
                }
            });

            $dispatcher = new \Phalcon\Mvc\Dispatcher;
            $dispatcher->setEventsManager($eventsManager);

            $dispatcher->setDefaultNamespace($controller_class);
            return $dispatcher;
        };

        // Set up module-specific configuration.
        $module_base_name = strtolower($this->_module_class_name);
        $module_config = $di->get('module_config');

        $di->setShared('current_module_config', function() use ($module_base_name, $module_config) {
            if (isset($module_config[$module_base_name]))
                return $module_config[$module_base_name];
            else
                return null;
        });

        // Set up the view component and shared templates.
        $views_dir = 'modules/'.$module_base_name.'/views/scripts/';

        $di['view'] = function () use($views_dir) {
            return \App\Phalcon\View::getView(array(
                'views_dir' => $views_dir,
            ));
        };
    }

}
