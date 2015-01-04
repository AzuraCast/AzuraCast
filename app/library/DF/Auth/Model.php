<?php
namespace DF\Auth;

use \Entity\User;

class Model extends Instance
{
    public function __construct()
    {
        parent::__construct();
        $this->_adapter = new Adapter\Model;
    }
    
    public function authenticate($credentials = NULL)
    {
        $this->_adapter->setOptions($credentials);
        
        $response = parent::authenticate();
        if($response->isValid())
        {
            $identity = $response->getIdentity();
            $user = User::find($identity['id']);

            $this->setUser($user);
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