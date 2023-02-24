<?php

declare(strict_types=1);

namespace App\Session;

use Mezzio\Session\SessionInterface;

/**
 * Quick message queue service.
 */
final class Flash
{
    public const SESSION_KEY = 'flash';

    private ?array $messages = null;

    public function __construct(
        private readonly SessionInterface $session
    ) {
    }

    public function success(
        string $message,
        bool $saveInSession = true
    ): void {
        $this->addMessage($message, FlashLevels::Success, $saveInSession);
    }

    public function warning(
        string $message,
        bool $saveInSession = true
    ): void {
        $this->addMessage($message, FlashLevels::Warning, $saveInSession);
    }

    public function error(
        string $message,
        bool $saveInSession = true
    ): void {
        $this->addMessage($message, FlashLevels::Error, $saveInSession);
    }

    public function info(
        string $message,
        bool $saveInSession = true
    ): void {
        $this->addMessage($message, FlashLevels::Info, $saveInSession);
    }

    public function addMessage(
        string $message,
        FlashLevels $level = FlashLevels::Info,
        bool $saveInSession = true
    ): void {
        $messageRow = [
            'text' => $message,
            'color' => $level->value,
        ];

        $this->getMessages();
        $this->messages[] = $messageRow;

        if ($saveInSession) {
            $messages = (array)$this->session->get(self::SESSION_KEY);
            $messages[] = $messageRow;

            $this->session->set(self::SESSION_KEY, $messages);
        }
    }

    /**
     * Indicate whether messages are currently pending display.
     */
    public function hasMessages(): bool
    {
        $messages = $this->getMessages();
        return (count($messages) > 0);
    }

    /**
     * Return all messages, removing them from the internal storage in the process.
     *
     * @return mixed[]
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
