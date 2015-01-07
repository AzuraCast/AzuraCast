<?php
namespace DF\Auth;

use \Entity\User;

class Cas extends Instance
{
    public function __construct()
    {
        parent::__construct();

        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $this->_adapter = new Adapter\Cas($config->services->cas->toArray());
    }
    
    public function authenticate()
    {
        $response = parent::authenticate();
        
        if($response->isValid())
        {
            $identity = $response->getIdentity();
            $user = User::getOrCreate($identity['uin']);
            
            $this->_session->identity = $identity;
            $this->_session->user_id = $user['id'];
            $this->_user = $user;
            return true;
        }
        else
        {
            if($response->getCode() != \Zend_Auth_Result::FAILURE_UNCATEGORIZED)
            {
                foreach($response->getMessages() as $message)
                    \DF\Flash::addMessage($message);
            }
            return false;
        }
    }
}