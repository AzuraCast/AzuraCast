<?php

declare(strict_types=1);

namespace App\Service\Avatar;

interface AvatarServiceInterface
{
    public function getServiceName(): string;

    public function getServiceUrl(): string;

    public function getAvatar(string $email, int $size = 50, ?string $default = null): string;
}
