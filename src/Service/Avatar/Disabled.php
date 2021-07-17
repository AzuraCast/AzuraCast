<?php

declare(strict_types=1);

namespace App\Service\Avatar;

class Disabled implements AvatarServiceInterface
{
    public function getServiceName(): string
    {
        return 'Disabled';
    }

    public function getServiceUrl(): string
    {
        return '';
    }

    public function getAvatar(string $email, int $size = 50, ?string $default = null): string
    {
        return $default ?? '';
    }
}
