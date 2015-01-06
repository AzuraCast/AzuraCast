<?php
/**
 * DF\Auth - Static Wrapper for the global auth instance.
 */
namespace DF;

class Auth
{
    public static function getInstance()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('auth');
    }
    
    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();
        return call_user_func_array(array($instance, $name), $arguments);
    }
}