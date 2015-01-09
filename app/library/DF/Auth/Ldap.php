<?php
namespace DF\Auth;

use \Entity\User;
use \Entity\Role;

class Ldap extends Instance
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_adapter = new Adapter\Ldap;
    }
    
    public function authenticate($credentials = NULL)
    {       
        $this->_adapter->setOptions($credentials);
        
        $response = parent::authenticate();
        if($response->isValid())
        {
            $identity = $response->getIdentity();
            $this->_session->identity = $identity;
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
        
        $user = User::getOrCreate($identity, 'ldap');
        $this->_session->user_id = $user['id'];
        $this->_user = $user;
        return true;
    }
    
    public function getUser()
    {
        $user = parent::getUser();
        $identity = $this->getIdentity();
        
        if ($user instanceof User)
        {
            return $user;
        }
        elseif ($identity)
        {
            $user = User::getOrCreate($identity, 'ldap');
            
            if ($user instanceof User)
            {
                $this->_session->user_id = $user['id'];
                $this->_user = $user;
                return $user;
            }
            
            return false;
        }
    }
}