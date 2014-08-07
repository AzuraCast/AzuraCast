<?php
/**
 * Doctrine DB Model Custom Authentication Adapter
 */

namespace DF\Auth\Adapter;

use \Entity\User;

class Model implements \Zend_Auth_Adapter_Interface
{
    protected $_options = array();

    public function __construct($options = array())
    {
        $this->setOptions($options);
    }
    
    public function setOptions($options)
    {
        $this->_options = array_merge($this->_options, (array)$options);
    }
    
    public function authenticate()
    {
        $user = $this->modelAuth($this->_options['username'], $this->_options['password']);

        if ($user !== FALSE)
        {
            return new \Zend_Auth_Result(
                \Zend_Auth_Result::SUCCESS,
                array('id' => $user['id']),
                array()
            );
        }
        else
        {
            return new \Zend_Auth_Result(
                \Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
                array('Invalid username or password supplied. Please try again.')
            );
        }
    }
    
    public function modelAuth($username, $password)
    {
        return \Entity\User::authenticate($username, $password);
    }
}