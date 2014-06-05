<?php
namespace DF;
class Csrf
{
    public static function getToken()
    {
        $session = new \Zend_Session_Namespace('csrf');
        if( !isset($session->token) )
            self::resetToken();

        return $session->token;
    }

    public static function validateToken($token)
    {
        $session = new \Zend_Session_Namespace('csrf');
        $old_token = $session->token;
        
        self::resetToken();
        
        return ($token == $old_token);
    }

    public static function resetToken()
    {
        $session = new \Zend_Session_Namespace('csrf');
        $session->token = sprintf("%s:%d", md5(uniqid(mt_rand(), true)), time());
    }
}