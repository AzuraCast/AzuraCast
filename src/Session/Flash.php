<?php
namespace App\Session;

use App\Traits\AvailableStaticallyTrait;
use Mezzio\Session\SessionInterface;

/**
 * Quick message queue service.
 */
class Flash
{
    use AvailableStaticallyTrait;

    public const SESSION_KEY = 'flash';

    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'danger';
    public const INFO = 'info';

    protected ?array $messages = null;

    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Alias of addMessage.
     *
     * @param string $message
     * @param string $level
     * @param bool $saveInSession
     */
    public function alert($message, $level = self::INFO, $saveInSession = true): void
    {
        $this->addMessage($message, $level, $saveInSession);
    }

    /**
     * Add a message to the flash message queue.
     *
     * @param string $message
     * @param string $level
     * @param bool $saveInSession
     */
    public function addMessage($message, $level = self::INFO, $saveInSession = true): void
    {
        $colorChart = [
            'green' => self::SUCCESS,
            'success' => self::SUCCESS,
            'yellow' => self::WARNING,
            'warning' => self::WARNING,
            'red' => self::ERROR,
            'error' => self::ERROR,
            'danger' => self::ERROR,
            'info' => self::INFO,
            'blue' => self::INFO,
            'default' => '',
        ];

        $messageRow = [
            'text' => $message,
            'color' => $colorChart[$level] ?? $level,
        ];

        if (null === $this->messages) {
            $this->getMessages();
        }
        $this->messages[] = $messageRow;

        if ($saveInSession) {
            $messages = (array)$this->session->get(self::SESSION_KEY);
            $messages[] = $messageRow;

            $this->session->set(self::SESSION_KEY, $messages);
        }
    }

    /**
     * Indicate whether messages are currently pending display.
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        $messages = $this->getMessages();
        return (count($messages) > 0);
    }

    /**
     * Return all messages, removing them from the internal storage in the process.
     *
     * @return array
     */
    public function getMessages(): array
    {
        if (null === $this->messages) {
            if ($this->session->has(self::SESSION_KEY)) {
                $this->messages = (array)$this->session->get(self::SESSION_KEY);
                $this->session->unset(self::SESSION_KEY);
            } else {
                $this->messages = [];
            }
        }

        return $this->messages;
    }
}
