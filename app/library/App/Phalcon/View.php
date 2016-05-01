<?php
namespace App\Phalcon;

use Phalcon\Mvc\View\Engine\Volt;

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
            'base_dir'      => APP_INCLUDE_BASE.'/',
            'views_dir'     => 'modules/frontend/views/scripts/',
            'partials_dir'  => '',
            'layouts_dir'   => '../../../../templates/',
            'layout'        => 'main',
        );
        $options = array_merge($defaults, (array)$options);

        // Temporary fix to force "views_dir" to be the full path, because "base_dir" is not used in some Phalcon calculations.
        $options['views_dir'] = $options['base_dir'].$options['views_dir'];
        $options['base_dir'] = '';

        $view = new \Phalcon\Mvc\View();
        $view->setDI($di);

        $eventsManager = new \Phalcon\Events\Manager();
        $view->setEventsManager($eventsManager);

        // Base directory from which all views load.
        $view->setBasePath($options['base_dir']);
        $view->setViewsDir($options['views_dir']);

        // Relative path of main templates.
        $view->setLayoutsDir($options['layouts_dir']);
        $view->setTemplateAfter($options['layout']);

        // Use present directory for partials by default.
        $view->setPartialsDir($options['partials_dir']);

        // Register template engines.
        $view->registerEngines(array(
            ".phtml" => 'Phalcon\Mvc\View\Engine\Php',
            ".volt" => function($view, $di) {

                $volt = new Volt($view, $di);
                $volt->setOptions(array(
                    'compileAlways' => (APP_APPLICATION_ENV == 'development'),
                    'compiledPath' => function($templatePath) {
                        // Clean up the template path and remove non-application folders from path.
                        $templatePath = realpath($templatePath);
                        $templatePath = ltrim(str_replace(APP_INCLUDE_BASE, '', $templatePath), '/');

                        $find_replace = array(
                            '/views/scripts/' => '_',
                            '../' => '',
                            '/' => '_',
                            '.volt' => '',
                        );
                        $templatePath = str_replace(array_keys($find_replace), array_values($find_replace), $templatePath);

                        return APP_INCLUDE_CACHE.'/volt_'.$templatePath.'.compiled.php';
                    }
                ));

                $compiler = $volt->getCompiler();
                $compiler->addExtension(new \App\Phalcon\Service\ViewHelper());

                return $volt;
            }
        ));

        return $view;
    }
}