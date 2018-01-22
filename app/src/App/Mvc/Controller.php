<?php
namespace App\Mvc;

use App\Acl;
use App\Config;
use App\Url;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

use Slim\Http\Response as Response;
use Slim\Http\Request as Request;

class Controller
{
    /** @var ContainerInterface */
    protected $di;

    /** @var Config */
    protected $config;

    /** @var View */
    protected $view;

    /** @var Url */
    protected $url;

    /** @var EntityManager */
    protected $em;

    /** @var Acl */
    protected $acl;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $module;

    /** @var string */
    protected $controller;

    /** @var string */
    protected $action;

    /** @var array */
    protected $params;

    /** @var string|null */
    protected $_cache_privacy = null;

    /** @var int */
    protected $_cache_lifetime = 0;

    public function __construct(ContainerInterface $di, $module, $controller, $action)
    {
        $this->di = $di;

        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;

        $this->config = $di[Config::class];
        $this->view = $di[View::class];
        $this->url = $di[Url::class];
        $this->em = $di[EntityManager::class];
        $this->acl = $di[Acl::class];

        $common_views_dir = APP_INCLUDE_MODULES.'/'.$module.'/views';
        if (is_dir($common_views_dir)) {
            $this->view->setFolder('common', $common_views_dir);

            $controller_views_dir = $common_views_dir . '/' . $controller;
            if (is_dir($controller_views_dir)) {
                $this->view->setFolder('controller', $controller_views_dir);
            }
        }
    }

    /**
     * Handle the MVC-style dispatching of a controller action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     * @throws \App\Exception\NotLoggedIn
     * @throws \App\Exception\PermissionDenied
     */
    public function dispatch(Request $request, Response $response, $args)
    {
        $this->request = $request;
        $this->response = $response;
        $this->params = $args;

        $this->url->setCurrentRoute([
            'module' => $this->module,
            'controller' => $this->controller,
            'action' => $this->action,
            'params' => $args,
        ]);

        $init_result = $this->init();
        if ($init_result instanceof Response) {
            return $init_result;
        }

        $predispatch_result = $this->preDispatch();
        if ($predispatch_result instanceof Response) {
            return $predispatch_result;
        }

        $action_name = $this->action . 'Action';
        $action_result = $this->$action_name();
        if ($action_result instanceof Response) {
            return $action_result;
        }

        if (!$this->view->isDisabled()) {
            return $this->render();
        }

        return $this->response;
    }

    /**
     * Default initialization function.
     *
     * @return null
     * @throws \App\Exception\NotLoggedIn
     * @throws \App\Exception\PermissionDenied
     */
    public function init()
    {
        $isAllowed = $this->permissions();
        if (!$isAllowed) {
            /** @var \App\Auth $auth */
            $auth = $this->di[\App\Auth::class];

            if (!$auth->isLoggedIn()) {
                throw new \App\Exception\NotLoggedIn;
            } else {
                throw new \App\Exception\PermissionDenied;
            }
        }

        return null;
    }

    /**
     * Overridable function that runs after permission checks, but before the page is "dispatched".
     * Commonly used to store variables shared across multiple actions.
     *
     * @return bool
     */
    protected function preDispatch()
    {
        return true;
    }

    /**
     * Overridable permissions check. Return false to generate "access denied" message.
     * @return bool
     */
    protected function permissions()
    {
        return true;
    }

    /**
     * Set new HTTP cache "privacy" level, used by intermediate caches.
     *
     * @param $new_privacy "private" or "public"
     */
    public function setCachePrivacy($new_privacy)
    {
        $this->_cache_privacy = strtolower($new_privacy);
    }

    /**
     * Set new HTTP cache "lifetime", expressed as seconds after current time.
     *
     * @param $new_lifetime
     */
    public function setCacheLifetime($new_lifetime)
    {
        $this->_cache_lifetime = (int)$new_lifetime;
    }

    /**
     * Internal cache handling after page handling is complete.
     */
    protected function handleCache()
    {
        // Set default caching parameters for pages that do not customize it.
        if ($this->_cache_privacy === null) {
            /** @var \App\Auth $auth */
            $auth = $this->di[\App\Auth::class];

            if ($auth->isLoggedIn()) {
                $this->_cache_privacy = 'private';
                $this->_cache_lifetime = 0;
            } else {
                $this->_cache_privacy = 'public';
                $this->_cache_lifetime = 30;
            }
        }

        if ($this->_cache_privacy == 'private') {
            // $this->response->setHeader('Cache-Control', 'must-revalidate, private, max-age=' . $this->_cache_lifetime);
            $this->response = $this->response->withHeader('X-Accel-Expires', 'off');
        } else {
            // $this->response->setHeader('Cache-Control', 'public, max-age=' . $this->_cache_lifetime);
            $this->response = $this->response->withHeader('X-Accel-Expires', $this->_cache_lifetime);
        }
    }

    /**
     * Retrieve parameter from request.
     *
     * @param $param_name
     * @param null $default_value
     * @return mixed|null
     */
    public function getParam($param_name, $default_value = null)
    {
        $query_params = $this->request->getParams();

        if (isset($this->params[$param_name])) {
            return $this->params[$param_name];
        } elseif (isset($query_params[$param_name])) {
            return $query_params[$param_name];
        } else {
            return $default_value;
        }
    }

    /**
     * Detect if parameter is present in request.
     *
     * @param $param_name
     * @return bool
     */
    public function hasParam($param_name)
    {
        return ($this->getParam($param_name) !== null);
    }

    /**
     * Trigger rendering of template.
     *
     * @param null $template_name
     * @return Response
     */
    public function render($template_name = null, $template_args = [])
    {
        if ($template_name === null) {
            $template_name = 'controller::' . $this->action;
        }

        $this->response = $this->response->withHeader('Content-type', 'text/html; charset=utf-8');

        $template = $this->view->render($template_name, $template_args);

        $body = $this->response->getBody();
        $body->write($template);

        return $this->response->withBody($body);
    }

    /**
     * Render a form using the system's form template.
     *
     * @param \App\Form $form
     * @param string $mode
     * @param null $form_title
     * @return Response
     */
    protected function renderForm(\App\Form $form, $mode = 'edit', $form_title = null)
    {
        if ($form_title) {
            $this->view->title = $form_title;
        }

        $this->view->form = $form;
        $this->view->render_mode = $mode;

        return $this->render('system/form_page');
    }

    /**
     * Disable rendering of template for this page view.
     */
    public function doNotRender()
    {
        $this->view->disable();
    }

    /**
     * Render the page output as the supplied JSON.
     *
     * @param $json_data
     * @return Response
     */
    public function renderJson($json_data)
    {
        $this->doNotRender();

        return $this->response
            ->withHeader('Content-type', 'application/json; charset=utf-8')
            ->write(json_encode($json_data));
    }

    /**
     * @param string $file_path
     * @param string|null $file_name
     * @return Response
     */
    public function renderFile($file_path, $file_name = null)
    {
        $this->doNotRender();
        set_time_limit(600);

        if ($file_name == null) {
            $file_name = basename($file_path);
        }

        $this->response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', mime_content_type($file_path))
            ->withHeader('Content-Length', filesize($file_path))
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);

        $fh = fopen($file_path, 'rb');
        $stream = new \Slim\Http\Stream($fh);

        return $this->response->withBody($stream);
    }

    /**
     * @param string $file_data The body of the file contents.
     * @param string $content_type The HTTP header content-type (i.e. text/csv)
     * @param string|null $file_name
     * @return Response
     */
    public function renderStringAsFile($file_data, $content_type, $file_name = null)
    {
        $this->doNotRender();

        $this->response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $content_type);

        if ($file_name !== null) {
            $this->response = $this->response->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);
        }

        return $this->response->write($file_data);
    }

    /* URL Redirection */

    /**
     * Redirect to the URL specified.
     *
     * @param $new_url
     * @param int $code
     * @return Response
     */
    public function redirect($new_url, $code = 302)
    {
        $this->doNotRender();

        return $this->response
            ->withStatus($code)
            ->withHeader('Location', $new_url);
    }

    /**
     * Redirect to the route specified.
     *
     * @param $route
     * @param int $code
     * @return Response
     */
    public function redirectToRoute($route, $code = 302)
    {
        return $this->redirect($this->url->route($route), $code);
    }

    /**
     * Redirect with parameters from the current URL.
     *
     * @param mixed $route
     * @param int $code
     * @return Response
     */
    public function redirectFromHere($route, $code = 302)
    {
        return $this->redirect($this->url->routeFromHere($route), $code);
    }

    /**
     * Redirect to the current page.
     *
     * @param int $code
     * @return Response
     */
    public function redirectHere($code = 302)
    {
        return $this->redirect($_SERVER['REQUEST_URI'], $code);
    }

    /**
     * Redirect to the homepage.
     *
     * @param int $code
     * @return Response
     */
    public function redirectHome($code = 302)
    {
        return $this->redirect($this->url->named('home'), $code);
    }

    /**
     * Redirect with parameters to named route.
     *
     * @param string $name
     * @param int $code
     * @return Response
     */
    public function redirectToName($name, $route_params = [], $code = 302)
    {
        return $this->redirect($this->url->named($name, $route_params), $code);
    }

    /**
     * Store the current referring page in a session variable.
     *
     * @param string $namespace
     * @param bool $loose
     */
    protected function storeReferrer($namespace = 'default', $loose = true)
    {
        $session = $this->_getReferrerStorage($namespace);

        if (!isset($session->url) || ($loose && isset($session->url) && $this->url->current() != $this->url->referrer())) {
            $session->url = $this->url->referrer();
        }
    }

    /**
     * Retrieve the referring page stored in a session variable (if it exists).
     *
     * @param string $namespace
     * @return mixed
     */
    protected function getStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        return $session->url;
    }

    /**
     * Clear any session variable storing referrer data.
     *
     * @param string $namespace
     */
    protected function clearStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        unset($session->url);
    }

    /**
     * Redirect to a session-stored referrer URI, or a specified URI if none exists.
     *
     * @param string $namespace
     * @param bool $default_url
     * @return Response
     */
    protected function redirectToStoredReferrer($namespace = 'default', $default_url = false)
    {
        $referrer = $this->getStoredReferrer($namespace);
        $this->clearStoredReferrer($namespace);

        $home_url = $this->url->named('home');
        if (strcmp($referrer, $this->request->getUri()->getPath()) == 0) {
            $referrer = $home_url;
        }

        if (trim($referrer) == '') {
            $referrer = ($default_url) ? $default_url : $home_url;
        }

        return $this->redirect($referrer);
    }

    /**
     * @param string $namespace
     * @return \App\Session\NamespaceInterface
     */
    protected function _getReferrerStorage($namespace = 'default'): \App\Session\NamespaceInterface
    {
        /** @var \App\Session $session_manager */
        $session_manager = $this->di[\App\Session::class];
        return $session_manager->get('referrer_' . $namespace);
    }

    /**
     * Store a message for display as a "flash" style success/error/warning message.
     *
     * @param $message
     * @param string $level
     */
    public function alert($message, $level = \App\Flash::INFO)
    {
        /** @var \App\Flash $flash */
        $flash = $this->di[\App\Flash::class];
        $flash->addMessage($message, $level, true);
    }
}