<?php

declare(strict_types=1);

namespace App\Service\Avatar;

final class Libravatar implements AvatarServiceInterface
{
    public const BASE_URL = 'https://seccdn.libravatar.org/avatar';

    public function getServiceName(): string
    {
        return 'Libravatar';
    }

    public function getServiceUrl(): string
    {
        return 'https://libravatar.org';
    }

    public function getAvatar(string $email, int $size = 50, ?string $default = 'mm'): string
    {
        $urlParams = [
            'd' => $default,
            'size' => $size,
        ];

        $avatarUrl = self::BASE_URL . '/' . md5(strtolower($email)) . '?' . http_build_query($urlParams);
        return htmlspecialchars($avatarUrl, ENT_QUOTES | ENT_HTML5);
    }
}
