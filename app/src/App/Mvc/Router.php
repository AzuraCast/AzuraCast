<?php
namespace App\Mvc;

use App\Url;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use RuntimeException;

class Router
{
    /** @var Container */
    protected $di;

    /** @var View */
    protected $view;

    /** @var Url */
    protected $url;

    /** @var string */
    protected $module;

    /** @var string */
    protected $controller;

    /** @var string */
    protected $action;

    public function __construct(Container $di, View $view, Url $url)
    {
        $this->di = $di;
        $this->view = $view;
        $this->url = $url;
    }

    public function setRoute($route)
    {
        list($this->module, $this->controller, $this->action) = explode(':', $route);

        $class = $this->_getClass();
        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Controller %s does not exist', $class));
        }

        $common_views_dir = APP_INCLUDE_MODULES.'/'.$this->module.'/views';
        if (is_dir($common_views_dir)) {
            $this->view->setFolder('common', $common_views_dir);

            $controller_views_dir = $common_views_dir . '/' . $this->controller;
            if (is_dir($controller_views_dir)) {
                $this->view->setFolder('controller', $controller_views_dir);
            }
        }
    }

    public function dispatch(Request $request, Response $response, $args): Response
    {
        $this->url->setCurrentRoute([
            'module' => $this->module,
            'controller' => $this->controller,
            'action' => $this->action,
            'params' => $args,
        ]);

        $class = $this->_getClass();

        /** @var \App\Mvc\Controller $controller */
        $controller = new $class($this->di);

        return $controller->dispatch($request, $response, $args);
    }

    protected function _getClass()
    {
        return '\\Controller\\' . ucfirst($this->module) . '\\' . ucfirst($this->controller) . 'Controller';
    }

}