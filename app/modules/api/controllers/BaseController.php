<?php
namespace Modules\Api\Controllers;

class BaseController extends \DF\Phalcon\Controller
{
    public function permissions()
    {
        return true;
    }

    protected $_time_start;

    public function preDispatch()
    {
        parent::preDispatch();

        // Disable rendering.
        $this->doNotRender();

        // Allow AJAX retrieval.
        $this->response->setHeader('Access-Control-Allow-Origin', '*');

        /*
        // Fix the base URL prefixed with '//'.
        \DF\Url::forceSchemePrefix();
        */

        $this->_time_start = microtime(true);
    }

    public function postDispatch()
    {
        parent::postDispatch();

        $end_time = microtime(true);
        $request_time = $end_time - $this->_time_start;

        // Log request using a raw SQL query for higher performance.
        $table_name = $this->em->getClassMetadata('\Entity\ApiCall')->getTableName();
        $conn = $this->em->getConnection();

        $conn->insert($table_name, array(
            'timestamp'     => time(),
            'ip'            => $_SERVER['REMOTE_ADDR'],
            'client'        => $this->_getParam('client', 'general'),
            'useragent'     => $_SERVER['HTTP_USER_AGENT'],
            'controller'    => $this->dispatcher->getControllerName(),
            'action'        => $this->dispatcher->getActionName(),
            'parameters'    => json_encode($_REQUEST),
            'requesttime'   => $request_time,
        ));
    }

    /**
     * Authentication
     */

    public function requireKey()
    {
        $this->returnError('API keys are not yet implemented.');
        return;
    }

    /**
     * Result Printout
     */

    public function returnSuccess($data)
    {
        $this->returnToScreen(array(
            'status'    => 'success',
            'result'    => $data,
        ));
        return true;
    }

    public function returnError($message)
    {
        $this->returnToScreen(array(
            'status'    => 'error',
            'error'     => $message,
        ));
        return false;
    }

    public function returnToScreen($obj)
    {
        $format = strtolower($this->getParam('format', 'json'));

        if ($format == 'xml')
            $this->returnRaw(\DF\Export::ArrayToXml($obj), 'xml');
        else
            $this->returnRaw(json_encode($obj, JSON_UNESCAPED_SLASHES), 'json');
    }

    public function returnRaw($message, $format = 'json')
    {
        if ($format == 'xml')
            $this->response->setContentType('text/xml', 'utf-8');
        else
            $this->response->setContentType('application/json', 'utf-8');

        $this->response->setContent($message);
        $this->response->send();
    }
}