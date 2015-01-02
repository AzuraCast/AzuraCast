<?php
/**
 * LDAP Authentication Adapter
 */

namespace DF\Auth\Adapter;

define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);

class Ldap implements \Zend_Auth_Adapter_Interface
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

    /**
     * (non-PHPdoc)
     * @see Zend/Auth/Adapter/Zend_Auth_Adapter_Interface#authenticate()
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        return \DF\Service\Ldap::authenticate($this->_options['username'], $this->_options['password']);
    }
}