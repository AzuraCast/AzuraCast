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

    protected $messages = [];

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
    public function addMessage($message, $level = self::INFO, $save_in_session = true): void
    {
        $color_chart = [
            'green' => 'success',
            'success' => 'success',
            'yellow' => 'warning',
            'warning' => 'warning',
            'red' => 'danger',
            'error' => 'danger',
            'info' => 'info',
            'blue' => 'info',
            'default' => '',
        ];

        $message_row = [
            'text' => $message,
            'color' => (isset($color_chart[$level])) ? $color_chart[$level] : $color_chart['default'],
        ];

        $message_hash = md5(json_encode($message_row));

        $this->messages[$message_hash] = $message_row;

        if ($save_in_session) {
            $messages = $this->_session->messages;
            $messages[$message_hash] = $message_row;
            $this->_session->messages = $messages;
        }
    }

    /**
     * Alias of addMessage.
     *
     * @param $message
     * @param string $level
     * @param bool $save_in_session
     */
    public function alert($message, $level = self::INFO, $save_in_session = true): void
    {
        $this->addMessage($message, $level, $save_in_session);
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

        $this->messages = [];
        unset($this->_session->messages);

        return (count($messages) > 0) ? $messages : false;
    }
}