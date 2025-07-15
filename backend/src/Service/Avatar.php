<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\SettingsAwareTrait;
use App\Service\Avatar\AvatarServiceInterface;
use App\Service\Avatar\Disabled;
use App\Service\Avatar\Gravatar;
use App\Service\Avatar\Libravatar;

final class Avatar
{
    use SettingsAwareTrait;

    public const int DEFAULT_SIZE = 64;

    public const string DEFAULT_AVATAR = 'https://www.azuracast.com/img/avatar.png';

    public const string SERVICE_LIBRAVATAR = 'libravatar';
    public const string SERVICE_GRAVATAR = 'gravatar';
    public const string SERVICE_DISABLED = 'disabled';

    public const string DEFAULT_SERVICE = self::SERVICE_LIBRAVATAR;

    public function getAvatarService(): AvatarServiceInterface
    {
        $settings = $this->readSettings();

        return match ($settings->avatar_service ?? self::DEFAULT_SERVICE) {
            self::SERVICE_LIBRAVATAR => new Libravatar(),
            self::SERVICE_GRAVATAR => new Gravatar(),
            default => new Disabled()
        };
    }

    public function getAvatar(?string $email, int $size = self::DEFAULT_SIZE): string
    {
        $avatarService = $this->getAvatarService();

        $default = $this->readSettings()->avatar_default_url ?? self::DEFAULT_AVATAR;

        if (empty($email)) {
            return $default;
        }

        return $avatarService->getAvatar($email, $size, $default);
    }
}
