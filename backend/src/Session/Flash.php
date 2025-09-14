<?php

declare(strict_types=1);

namespace App\Session;

use App\Enums\FlashLevels;
use Mezzio\Session\SessionInterface;

/**
 * Quick message queue service.
 */
final class Flash
{
    public const string SESSION_KEY = 'flash';

    private ?array $messages = null;

    public function __construct(
        private readonly SessionInterface $session
    ) {
    }

    public function success(
        string $message,
        ?string $title = null
    ): void {
        $this->addMessage(
            message: $message,
            title: $title,
            level: FlashLevels::Success
        );
    }

    public function warning(
        string $message,
        ?string $title = null
    ): void {
        $this->addMessage(
            message: $message,
            title: $title,
            level: FlashLevels::Warning
        );
    }

    public function error(
        string $message,
        ?string $title = null
    ): void {
        $this->addMessage(
            message: $message,
            title: $title,
            level: FlashLevels::Error
        );
    }

    public function info(
        string $message,
        ?string $title = null
    ): void {
        $this->addMessage(
            message: $message,
            title: $title,
            level: FlashLevels::Info
        );
    }

    private function addMessage(
        string $message,
        ?string $title,
        FlashLevels $level,
    ): void {
        $messageRow = [
            'title' => $title,
            'text' => $message,
            'variant' => $level->value,
        ];

        $this->getMessages();
        $this->messages[] = $messageRow;

        $messages = (array)$this->session->get(self::SESSION_KEY);
        $messages[] = $messageRow;

        $this->session->set(self::SESSION_KEY, $messages);
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
