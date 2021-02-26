<?php

namespace App\Message;

class ForgotPasswordMessage extends AbstractUniqueMessage
{
    public int $userId;

    public string $locale;

    public function getIdentifier(): string
    {
        return 'ForgotPassword_' . $this->userId;
    }
}
