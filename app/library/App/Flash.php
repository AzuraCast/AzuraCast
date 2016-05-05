<?php
/**
 * Quick message queue service.
 */

namespace App;

class Flash
{
    const SUCCESS = 'success';
    const WARNING = 'warning';
    const ERROR = 'error';
    const INFO = 'info';

    protected $messages = array();

    /**
     * @var Session\Instance
     */
    protected $_session;

    public function __construct(Session $session)
    {
        $this->_session = $session->get('alerts');

        // Load any previously saved messages.
        $this->messages = (array)$this->_session->messages;
        unset($this->_session->messages);
    }

    /**
     * Add a message to the flash message queue.
     *
     * @param $message
     * @param string $level
     * @param bool|false $save_in_session
     */
    public function addMessage($message, $level = self::INFO, $save_in_session = false)
    {
        $color_chart = array(
            'green'     => 'success',
            'success'   => 'success',
            'yellow'    => 'warning',
            'warning'   => 'warning',
            'red'       => 'danger',
            'error'     => 'danger',
            'info'      => 'info',
            'blue'      => 'info',
            'default'   => '',
        );

        $message_row = array(
            'text'  => $message,
            'color' => (isset($color_chart[$level])) ? $color_chart[$level] : $color_chart['default'],
        );

        $this->messages[] = $message_row;

        if ($save_in_session)
        {
            $messages = $this->_session->messages;
            $messages[] = $message_row;
            $this->_session->messages = $messages;
        }
    }

    /**
     * Indicate whether messages are currently pending display.
     *
     * @return bool
     */
    public function hasMessages()
    {
        return (count($this->messages) > 0);
    }

    /**
     * Return all messages, removing them from the internal storage in the process.
     *
     * @return array|bool
     */
    public function getMessages()
    {
        $messages = $this->messages;

        $this->messages = array();
        unset($this->_session->messages);

        return (count($messages) > 0) ? $messages : false;
    }
}