<?php
namespace DF\Phalcon;

class Module implements \Phalcon\Mvc\ModuleDefinitionInterface
{
    protected $_module_class_name;
    protected $_module_dir;

    protected function setModuleInfo($class_name, $dir)
    {
        $this->_module_class_name = $class_name;
        $this->_module_dir = $dir;
    }

    public function registerAutoloaders()
    {
        $loader = new \Phalcon\Loader();

        $controller_class = 'Modules\\'.$this->_module_class_name.'\Controllers';
        $loader->registerNamespaces(array(
            $controller_class => $this->_module_dir . '/controllers/',
        ));

        $loader->register();
    }

    public function registerServices($di)
    {
        $controller_class = 'Modules\\'.$this->_module_class_name.'\Controllers';

        $di['dispatcher'] = function () use ($controller_class) {
            $dispatcher = new \Phalcon\Mvc\Dispatcher();
            $dispatcher->setDefaultNamespace($controller_class);
            return $dispatcher;
        };

        /**
         * Setting up the view component
         */

        $views_dir = $this->_module_dir . '/views/scripts/';

        $di['view'] = function () use($views_dir) {
            $view = new \Phalcon\Mvc\View();

            $view->setViewsDir($views_dir);
            $view->setLayoutsDir('../../../../templates');
            $view->setPartialsDir('../../../../templates/shared');

            $view->setTemplateAfter('main');

            $view->registerEngines(array(
                ".phtml" => 'Phalcon\Mvc\View\Engine\Php',
                ".volt" => 'Phalcon\Mvc\View\Engine\Volt'
            ));

            return $view;
        };
    }

}
