<?php
namespace PVL\Controller\Action;

class Api extends \DF\Controller\Action
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
        header('Access-Control-Allow-Origin: *');

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
            'controller'    => $this->_getControllerName(),
            'action'        => $this->_getActionName(),
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
    }

    public function returnError($message)
    {
        $this->returnToScreen(array(
            'status'    => 'error',
            'error'     => $message,
        ));
    }

    public function returnToScreen($obj)
    {
        $format = strtolower($this->_getParam('format', 'json'));

        switch($format)
        {
            case "xml":
                header('Content-Type: text/xml; charset=utf-8');
                echo \DF\Export::ArrayToXml($obj);
            break;

            case "json":
            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($obj, JSON_UNESCAPED_SLASHES);
            break;
        }
    }

}