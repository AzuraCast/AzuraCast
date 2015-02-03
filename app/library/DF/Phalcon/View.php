<?php
namespace DF\Phalcon;

class View
{
    /**
     * Generate a new View object with preset parameters.
     *
     * @param array $options
     * @param \Phalcon\DiInterface $di
     * @return \Phalcon\Mvc\View
     */
    public static function getView($options = array(), \Phalcon\DiInterface $di = null)
    {
        if ($di == null)
            $di = \Phalcon\Di::getDefault();

        $defaults = array(
            'base_dir'      => DF_INCLUDE_BASE,
            'views_dir'     => 'modules/frontend/views/scripts',
            'partials_dir'  => '',
            'layouts_dir'   => '../../../../templates',
            'layout'        => 'main',
        );
        $options = array_merge($defaults, (array)$options);

        $view = new \Phalcon\Mvc\View();
        $view->setDI($di);

        $eventsManager = new \Phalcon\Events\Manager();
        $view->setEventsManager($eventsManager);

        // Base directory from which all views load.
        $view->setBasePath($options['base_dir']);
        $view->setViewsDir($options['views_dir']);

        // Relative path of main templates.
        $view->setLayoutsDir($options['layouts_dir']);
        $view->setLayout($options['layout']);

        // Use present directory for partials by default.
        $view->setPartialsDir($options['partials_dir']);

        // Register template engines.
        $view->registerEngines(array(
            ".phtml" => 'Phalcon\Mvc\View\Engine\Php',
            ".volt" => 'Phalcon\Mvc\View\Engine\Volt'
        ));

        // Register global escaper.
        $view->setVar('e', new \Phalcon\Escaper());

        return $view;
    }
}