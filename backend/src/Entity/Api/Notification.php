<?php

declare(strict_types=1);

namespace App\Entity\Api;

final class Notification
{
    public string $title;

    public string $body;

    public string $type;

    public ?string $actionLabel;

    public ?string $actionUrl;
}
