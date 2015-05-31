<?php
namespace DF\Phalcon;

use \DF\Session;
use \DF\Url;

class Controller extends \Phalcon\Mvc\Controller
{
    /* Phalcon Initialization */

    public function beforeExecuteRoute()
    {
        $this->init();
        return $this->preDispatch();
    }

    public function init()
    {
        $isAllowed = $this->permissions();
        if (!$isAllowed)
        {
            if (!$this->auth->isLoggedIn())
                throw new \DF\Exception\NotLoggedIn;
            else
                throw new \DF\Exception\PermissionDenied;
        }
    }

    protected function preDispatch()
    {
        $is_ajax = ($this->isAjax());
        $this->view->is_ajax = $is_ajax;

        if ($is_ajax)
        {
            $this->view->cleanTemplateAfter();
            $this->view->setLayout(null);
        }

        if ($this->hasParam('debug') && $this->_getParam('debug') === 'true')
        {
            error_reporting(E_ALL & ~E_STRICT);
            ini_set('display_errors', 1);
        }

        // NewRelic Logging.
        if (function_exists('newrelic_name_transaction')) {
            $app_url = '/'.$this->dispatcher->getModuleName().'/'.$this->dispatcher->getControllerName().'/'.$this->dispatcher->getActionName();
            newrelic_name_transaction($app_url);
        }

        return true;
    }

    public function afterExecuteRoute()
    {
        $this->postDispatch();
        $this->handleCache();
    }

    /**
     * Overridable function called after page handling is complete.
     */
    protected function postDispatch()
    {}

    /**
     * Overridable permissions check. Return false to generate "access denied" message.
     * @return bool
     */
    protected function permissions()
    {
        return true;
    }

    /* HTTP Cache Handling */

    protected $_cache_privacy = null;
    protected $_cache_lifetime = 0;

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
        if ($this->_cache_privacy === null)
        {
            $auth = $this->di->get('auth');

            if ($auth->isLoggedIn())
            {
                $this->_cache_privacy = 'private';
                $this->_cache_lifetime = 0;
            }
            else
            {
                $this->_cache_privacy = 'public';
                $this->_cache_lifetime = 30;
            }
        }

        if ($this->_cache_privacy == 'private')
        {
            // $this->response->setHeader('Cache-Control', 'must-revalidate, private, max-age=' . $this->_cache_lifetime);
            $this->response->setHeader('X-Accel-Expires', 'off');
        }
        else
        {
            // $this->response->setHeader('Cache-Control', 'public, max-age=' . $this->_cache_lifetime);
            $this->response->setHeader('X-Accel-Expires', $this->_cache_lifetime);
        }
    }

    /* URL Parameter Handling */

    /**
     * Retrieve parameter from request.
     *
     * @param $param_name
     * @param null $default_value
     * @return mixed|null
     */
    public function getParam($param_name, $default_value = NULL)
    {
        if ($param = $this->dispatcher->getParam($param_name))
            return $param;
        elseif ($this->request->has($param_name))
            return $this->request->get($param_name);
        else
            return $default_value;
    }

    /**
     * Alias for getParam()
     *
     * @deprecated Use getParam() instead.
     * @param $param_name
     * @param null $default_value
     * @return mixed|null
     */
    public function _getParam($param_name, $default_value = NULL)
    {
        return $this->getParam($param_name, $default_value);
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
     * Alias for hasParam()
     *
     * @deprecated Use hasParam() instead.
     * @param $param_name
     * @return bool
     */
    public function _hasParam($param_name)
    {
        return $this->hasParam($param_name);
    }

    /**
     * Trigger rendering of template.
     *
     * @param null $template_name
     */
    public function render($template_name = NULL)
    {
        if ($template_name === null)
            $new_view = $this->dispatcher->getControllerName().'/'.$this->dispatcher->getActionName();
        elseif (stristr($template_name, '/') !== false)
            $new_view = $template_name;
        else
            $new_view = $this->dispatcher->getControllerName().'/'.$template_name;

        $this->view->pick(array($new_view));
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
     */
    public function renderJson($json_data)
    {
        $this->doNotRender();
        $this->response->setJsonContent($json_data);
    }

    /**
     * Determines if a request is sent using the XMLHTTPRequest (AJAX) method.
     *
     * @return mixed
     */
    public function isAjax()
    {
        return $this->request->isAjax();
    }

    /* URL Redirection */

    /**
     * Redirect to the URL specified.
     *
     * @param $new_url
     * @param int $code
     */
    public function redirect($new_url, $code = 302)
    {
        $this->doNotRender();

        return $this->response->redirect($new_url, $code);
    }

    /**
     * Redirect to the route specified.
     *
     * @param $route
     * @param int $code
     */
    public function redirectToRoute($route, $code = 302)
    {
        $this->doNotRender();

        return $this->response->redirect(Url::route($route, $this->di), $code);
    }

    /**
     * Redirect with parameters from the current URL.
     *
     * @param $url_params
     * @param int $code
     */
    public function redirectFromHere($route, $code = 302)
    {
        $this->doNotRender();

        return $this->response->redirect(Url::routeFromHere($route, $this->di), $code);
    }

    /**
     * Redirect to the current page.
     *
     * @param int $code
     */
    public function redirectHere($code = 302)
    {
        $this->doNotRender();

        return $this->response->redirect($this->request->getUri(), $code);
    }

    /**
     * Redirect to the homepage.
     *
     * @param int $code
     */
    public function redirectHome($code = 302)
    {
        $this->doNotRender();

        return $this->response->redirect($this->url->get(''), $code);
    }

    /**
     * Force redirection to a HTTPS secure URL.
     */
    protected function forceSecure()
    {
        if (DF_APPLICATION_ENV == 'production' && !DF_IS_SECURE)
        {
            $this->doNotRender();

            $url = 'https://'.$this->request->getHttpHost().$this->request->getURI();
            return $this->response->redirect($url, 301);
        }
    }

    /**
     * Force redirection to a non-HTTPS URL for content reasons.
     */
    protected function forceInsecure()
    {
        if (DF_APPLICATION_ENV == 'production' && DF_IS_SECURE)
        {
            $this->doNotRender();

            $url = 'http://'.$this->request->getHttpHost().$this->request->getURI();
            return $this->response->redirect($url, 301);
        }
    }

    /* Referrer storage */
    protected function storeReferrer($namespace = 'default', $loose = true)
    {
        $session = Session::get('referrer_'.$namespace);

        if( !isset($session->url) || ($loose && isset($session->url) && Url::current() != Url::referrer()) )
            $session->url = Url::referrer();
    }

    protected function getStoredReferrer($namespace = 'default')
    {
        $session = Session::get('referrer_'.$namespace);
        return $session->url;
    }

    protected function clearStoredReferrer($namespace = 'default')
    {
        $session = Session::get('referrer_'.$namespace);
        unset($session->url);
    }

    protected function redirectToStoredReferrer($namespace = 'default', $default_url = false)
    {
        $referrer = $this->getStoredReferrer($namespace);
        $this->clearStoredReferrer($namespace);

        if( trim($referrer) == '' )
            if( $default_url )
                $referrer = $default_url;
            else
                $referrer = Url::baseUrl();

        return $this->redirect($referrer);
    }

    protected function redirectToReferrer($default = false)
    {
        if( !$default )
            $default = Url::baseUrl();

        return $this->redirect(Url::referrer($default));
    }

    /* Notifications */

    public function flash($message, $level = \DF\Flash::INFO)
    {
        $this->alert($message, $level);
    }
    public function alert($message, $level = \DF\Flash::INFO)
    {
        \DF\Flash::addMessage($message, $level);
    }

    /* Form Rendering */

    protected function renderForm(\DF\Form $form, $mode = 'edit', $form_title = NULL)
    {
        $this->view->setViewsDir('modules/frontend/views/scripts/');

        // Show visible title.
        if ($form_title)
            $this->view->title = $form_title;

        $this->view->form = $form;
        $this->view->render_mode = $mode;

        $this->view->pick('system/form');

        /*
        $result = $this->view->getRender('system', 'form');

        $this->response->setContent($result);
        $this->response->send();
        */
    }

    /* Parameter Handling */

    protected function convertGetToParam()
    {
        return $this->redirectFromHere($_GET);
    }
}