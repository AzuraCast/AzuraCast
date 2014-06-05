<?php
namespace DF\Auth\Adapter;

class Cas implements \Zend_Auth_Adapter_Interface
{
    protected $_options;

    public function __construct($options)
    {
        $this->setOptions($options);
    }
    
    public function setOptions($options)
    {
        $this->_options = $options;
    }
    
    public function authenticate()
    {
        try
        {
            $auth_result = $this->login();
            
            if ($auth_result['success'])
            {
                $result = new \Zend_Auth_Result(
                    \Zend_Auth_Result::SUCCESS,
                    $auth_result,
                    array()
                );
            }
            else
            {
                $result = new \Zend_Auth_Result(
                    \Zend_Auth_Result::FAILURE_UNCATEGORIZED,
                    null,
                    array($auth_result['message'])
                );
            }
        }
        catch( \Exception $e )
        {
            $result = new \Zend_Auth_Result(
                \Zend_Auth_Result::FAILURE,
                null,
                (array)sprintf('%s',
                    $e->getMessage()
                )
            );
        }
        
        return $result;
    }
    
    public function login($destination_url = NULL)
    {
        // Get the CAS ticket if it has been set.
        $ticket = (isset($_REQUEST['ticket'])) ? $_REQUEST['ticket'] : '';
        
        if (is_null($destination_url) || empty($destination_url))
        {
            $destination_url = $this->getServiceUrl();
        }
        
        if (!empty($ticket))
        {
            $validate = (substr($ticket, 0, 2) == 'ST') ? 'serviceValidate' : 'proxyValidate';
            
            $query_string = array(
                'service'       => $destination_url,
                'ticket'        => $ticket,
            );
            $file_url = $this->_options['cas_base'].'/'.$validate.'?'.http_build_query($query_string);
            $file = file_get_contents($file_url);

            if (!$file)
            {
                throw new \Exception('Could Not Authenticate: The CAS service did not return a complete response.');
            }
        }
        else
        {
            $query_string = array('service' => $destination_url);
            
            if ($this->_options['renew'])
            {
                $query_string['renew'] = 'true';
            }
            
            // Redirect to login page.
            header("Location: ".$this->_options['cas_base'].'/login?'.http_build_query($query_string));
            exit;
        }
        
        $xml_array = \DF\Export::XmlToArray($file);
        $return_value = array();
        
        if (isset($xml_array['cas:serviceResponse']['cas:authenticationSuccess']))
        {
            $attributes = $xml_array['cas:serviceResponse']['cas:authenticationSuccess'][0]['cas:attributes'][0];
            
            $return_value['success'] = TRUE;
            $return_value['uin'] = $attributes['cas:tamuEduPersonUIN'];
            $return_value['netid'] = $attributes['cas:tamuEduPersonNetID'];
        }
        else
        {
            $return_value['success'] = FALSE;
            $return_value['message'] = $xml_array['cas:serviceResponse']['cas:authenticationFailure']['code'];
        }
        
        return $return_value;
    }
    
    public function logout($destination_url = NULL)
    {
        if ($this->_options['full_logout'])
        {
            if (is_null($destination_url))
            {
                $destination_url = $this->getServiceUrl();
            }
            
            $url_params = array(
                'service'       => $destination_url,
            );
            
            // Redirect to login page.
            header("Location: ".$this->_options['cas_base'].'/logout?'.http_build_query($url_params));
            exit;
        }
    }
    
    private function getServiceUrl()
    {
        return \DF\Url::current(TRUE, FALSE);
    }
}