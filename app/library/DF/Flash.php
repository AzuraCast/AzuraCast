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
        $session = \DF\Session::get('alerts');

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

        $messages = (array)$session->messages;
        $messages[] = array(
            'message' => $message,
            'color' => (isset($color_chart[$level])) ? $color_chart[$level] : $color_chart['default'],
        );

        $session->messages = $messages;
    }

    public static function hasMessages()
    {
        $session = \DF\Session::get('alerts');

        $messages = (array)$session->messages;
        return count($messages) > 0;
    }

    public static function getMessages()
    {
        $session = \DF\Session::get('alerts');

        $messages = (array)$session->messages;
        unset($session->messages);
        
        return (count($messages) > 0) ? $messages : false;
    }
}