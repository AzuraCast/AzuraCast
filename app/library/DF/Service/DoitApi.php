<?php
/**
 * DoIT API (CompassAPI) interface class.
 */

namespace DF\Service;

class DoitApi
{
    const TIMEOUT = 300;
    
    protected $_settings;
    protected $_http_client;
    
    public function __construct()
    {
        $config = \Zend_Registry::get('config');
        $this->_settings = $config->services->doitapi->toArray();
    }
        
    public function __call($method, $params = array())
    {
        return $this->request($method, $params);
    }
    
    /**
     * Custom Wrapper Functions
     */
    
    public function checkAvailability()
    {
        try
        {
            $response = $this->request('isServerAlive');
            return ($response);
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    public function logException($exception, $user = NULL)
    {
        try
        {
            $config = \Zend_Registry::get('config');
            
            return $this->request('logException', array($config->application->shortcode, array(
                'environment' => DF_APPLICATION_ENV,
                'user' => $user,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'stack_trace' => $exception->getTraceAsString(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'request_uri' => $_SERVER['REQUEST_URI'],
                'referrer' => $_SERVER['HTTP_REFERRER'],
                'request_params' => $_REQUEST,
            )));
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
    }
    
    /** 
     * Standard API Calls
     */
        
    protected function request($method, $params = array(), $id = NULL)
    {
        set_time_limit(self::TIMEOUT+30);
        
        $client = $this->getHttpClient();
        
        $client->setUri($this->_settings['service_uri']);
        
        $request_string = array(
            'jsonrpc'   => '2.0',
            'method'    => $method,
            'params'    => $params,
            'id'        => $id,
        );
        $request_string = json_encode($request_string);
        
        $client->setRawData($request_string, 'application/json');
        $client->setHeaders('X-Compass-Api-Request-Signature: '.$this->getRequestSignature($request_string));
        
        $response = $client->request('POST');
        
        if ($response->isSuccessful() && $this->validateResponse($response))
        {
            $response_text = $response->getBody();
            $response_json = json_decode($response_text, TRUE);
            
            if (isset($response_json['result']))
            {
                return $response_json['result'];
            }
        }
        else
        {
            throw new \Exception('Invalid Response (HTTP '.$response->getStatus().'): '.$response->getBody());
        }
    }

    protected function getHttpClient()
    {
        if (!is_object($this->_http_client))
        {
            if (DF_APPLICATION_ENV == "standalone")
            {
                $this->_http_client = new \Zend_Http_Client(NULL, array(
                    'adapter' => new \Zend_Http_Client_Adapter_Test,
                ));
            }
            else
            {
                $this->_http_client = new \Zend_Http_Client();
                $this->_http_client->setConfig(array(
                    'timeout'       => self::TIMEOUT,
                    'keepalive'     => true,
                ));
            }
        }
        
        $this->_http_client->resetParameters();
        return $this->_http_client;
    }
    
    protected function validateResponse($response)
    {
        $signature = $response->getHeader('X-Compass-Api-Response-Signature', 'unsigned');
        
        if(strtolower($signature) == 'unsigned')
        {
            if($response->hasData('error'))
                return true;
            else
                return false;
        }
        
        $hash = $this->getDataHash($response->getBody());
        
        return ($hash == $signature);
    }
    
    protected function getDataHash($data)
    {
        $hash = hash_hmac("sha256", $data, $this->_settings['private_key']);
        return $hash;
    }
    
    protected function getRequestSignature($data)
    {
        $hash = $this->getDataHash($data);
        
        return $this->_settings['api_key'].':'.$hash;
    }
}