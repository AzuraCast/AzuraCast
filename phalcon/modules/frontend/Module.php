<?php

namespace Modules\Frontend;

class Module
{

    public function registerAutoloaders()
    {

        $loader = new \Phalcon\Loader();

        $loader->registerNamespaces(array(
            'Modules\Frontend\Controllers' => __DIR__ . '/controllers/',
        ));

        $loader->register();
    }

    public function registerServices($di)
    {
        $di['dispatcher'] = function() {
            $dispatcher = new \Phalcon\Mvc\Dispatcher();
            $dispatcher->setDefaultNamespace("Modules\Frontend\Controllers");
            return $dispatcher;
        };

        /**
         * Setting up the view component
         */
        $di['view'] = function() {
            $view = new \Phalcon\Mvc\View();

            $view->setViewsDir(__DIR__ . '/views/');
            $view->setLayoutsDir('../../../templates');
            $view->setTemplateAfter('main');

            return $view;
        };

    }

}