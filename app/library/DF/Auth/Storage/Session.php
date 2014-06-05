<?php
namespace DF\Auth\Storage;

class Session extends \Zend_Auth_Storage_Session
{
    public function __construct($namespace = 'default', $member = self::MEMBER_DEFAULT)
    {
        $this->_namespace = $namespace;
        $this->_member    = $member;
        $this->_session   = \DF\Session::get('zend_auth_'.$this->_namespace);
    }
}