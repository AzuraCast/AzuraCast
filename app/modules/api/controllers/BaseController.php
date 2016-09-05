<?php
namespace Modules\Api\Controllers;

use Entity\ApiKey;

class BaseController extends \App\Phalcon\Controller
{
    public function permissions()
    {
        return true;
    }

    protected $_time_start;

    public function preDispatch()
    {
        parent::preDispatch();

        // Disable session creation.
        $session = $this->di->get('session');
        $session->disable();

        // Disable rendering.
        $this->doNotRender();

        // Allow AJAX retrieval.
        $this->response->setHeader('Access-Control-Allow-Origin', '*');;

        $this->_time_start = microtime(true);

        // Set all API calls to be public cache-controlled by default.
        $this->setCachePrivacy('public');
        $this->setCacheLifetime(30);
    }

    public function postDispatch()
    {
        parent::postDispatch();

        $end_time = microtime(true);
        $request_time = $end_time - $this->_time_start;

        // Log request using a raw SQL query for higher performance.
        if (isset($_SERVER['CF-Connecting-IP']))
            $remote_ip = $_SERVER['CF-Connecting-IP'];
        else
            $remote_ip = $_SERVER['REMOTE_ADDR'];

        $params = array_merge((array)$this->dispatcher->getParams(), (array)$this->request->getQuery());

        /*
        // Insert into Influx
        $influx = $this->di->get('influx');
        $influx->setDatabase('analytics');

        $influx->insert('api_calls', [
            'value'         => 1,
            'ip'            => $remote_ip,
            'client'        => $this->getParam('client', 'general'),
            'useragent'     => $_SERVER['HTTP_USER_AGENT'],
            'controller'    => $this->dispatcher->getControllerName().'/'.$this->dispatcher->getActionName(),
            'parameters'    => json_encode($params),
            'referrer'      => $_SERVER['HTTP_REFERER'],
            'is_ajax'       => ($this->isAjax() ? '1' : '0'),
            'requesttime'   => $request_time,
        ]);
        */
    }

    /**
     * Authentication
     */

    /**
     * Require that an API key be supplied by the requesting user.
     *
     * @throws \App\Exception\PermissionDenied
     */
    public function requireKey()
    {
        if (!$this->authenticate())
            throw new \App\Exception\PermissionDenied('No valid API key specified.');
    }

    /**
     * Check that the API key supplied by the requesting user is valid.
     *
     * @return bool
     */
    public function authenticate()
    {
        if (isset($_SERVER['X-API-Key']))
            $api_key = $_SERVER['X-API-Key'];
        elseif ($this->hasParam('key'))
            $api_key = $this->getParam('key');
        else
            return false;

        return ApiKey::authenticate($api_key);
    }

    /**
     * Result Printout
     */

    public function returnSuccess($data)
    {
        return $this->returnToScreen(array(
            'status'    => 'success',
            'result'    => $data,
        ));
    }

    public function returnError($message)
    {
        return $this->returnToScreen(array(
            'status'    => 'error',
            'error'     => $message,
        ));
    }

    public function returnToScreen($obj)
    {
        $format = strtolower($this->getParam('format', 'json'));

        if ($format == 'xml')
            return $this->returnRaw(\App\Export::ArrayToXml($obj), 'xml');
        else
            return $this->returnRaw(json_encode($obj, \JSON_UNESCAPED_SLASHES), 'json');
    }

    public function returnRaw($message, $format = 'json')
    {
        if ($format == 'xml')
            $this->response->setContentType('text/xml', 'utf-8');
        else
            $this->response->setContentType('application/json', 'utf-8');

        return $this->response->setContent($message);
    }
}