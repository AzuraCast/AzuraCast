<?php

namespace App\Notification;

use App\Session\Flash;

class Notification
{
    // Alert type constants.
    public const SUCCESS = Flash::SUCCESS;
    public const WARNING = Flash::WARNING;
    public const ERROR = Flash::ERROR;
    public const INFO = Flash::INFO;

    protected string $title;

    protected string $body;

    protected string $type;

    public function __construct(string $title, string $body, string $type)
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
