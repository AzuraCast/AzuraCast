<?php

namespace Baseapp\Extension;

use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;

/**
 * Adapter to use Markdown library as templating engine
 *
 * @package     base-app
 * @category    Library
 * @version     2.0
 */
class Markdown extends Engine implements EngineInterface
{

    protected $markdown;

    /**
     * Engine constructor
     *
     * @param \Phalcon\Mvc\ViewInterface $view
     * @param \Phalcon\DiInterface       $dependencyInjector
     */
    public function __construct($view, $dependencyInjector = null)
    {
        $parser = new \Baseapp\Library\Parsedown\ParsedownExtra();
        $this->markdown = $parser;

        parent::__construct($view, $dependencyInjector);
    }

    /**
     * Renders a view using the template engine
     *
     * @param string  $path
     * @param array   $params
     * @param boolean $mustClean
     */
    public function render($path, $params, $mustClean = false)
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }

        $content = $this->markdown->text(file_get_contents($path));
        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }

}
