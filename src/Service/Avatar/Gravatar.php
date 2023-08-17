<?php

declare(strict_types=1);

namespace App\Service\Avatar;

final class Gravatar implements AvatarServiceInterface
{
    public const BASE_URL = 'https://www.gravatar.com/avatar';

    public function getServiceName(): string
    {
        return 'Gravatar';
    }

    public function getServiceUrl(): string
    {
        return 'https://www.gravatar.com';
    }

    public function getAvatar(string $email, int $size = 50, ?string $default = 'mm'): string
    {
        $urlParams = [
            'd' => $default,
            'r' => 'g',
            'size' => $size,
        ];

        $avatarUrl = self::BASE_URL . '/' . md5(strtolower($email)) . '?' . http_build_query($urlParams);
        return htmlspecialchars($avatarUrl, ENT_QUOTES | ENT_HTML5);
    }
}
