<?php
/**
 * Quick message queue
 */

namespace DF;
class Flash
{
    const SUCCESS = 'success';
    const WARNING = 'warning';
    const ERROR = 'error';
    const INFO = 'info';

    public static function addMessage($message, $level = self::INFO)
    {
        $session = new \Zend_Session_Namespace('DF_Flash');

        $color_chart = array(
            'green'     => 'success',
            'success'   => 'success',
            'yellow'    => 'warning',
            'warning'   => 'warning',
            'red'       => 'error',
            'error'     => 'error',
            'info'      => 'info',
            'blue'      => 'info',
            'default'   => 'info',
        );

        $session->messages[] = array(
            'message' => $message,
            'color' => (isset($color_chart[$level])) ? $color_chart[$level] : $color_chart['default'],
        );
    }

    public static function hasMessages()
    {
        $session = new \Zend_Session_Namespace('DF_Flash');

        $messages = (array)$session->messages;
        return count($messages) > 0;
    }

    public static function getMessages()
    {
        $session = new \Zend_Session_Namespace('DF_Flash');

        $messages = (array)$session->messages;
        unset($session->messages);
        
        return (count($messages) > 0) ? $messages : false;
    }
}