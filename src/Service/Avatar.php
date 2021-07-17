<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Repository\SettingsRepository;
use App\Service\Avatar\AvatarServiceInterface;
use App\Service\Avatar\Disabled;
use App\Service\Avatar\Gravatar;
use App\Service\Avatar\Libravatar;

class Avatar
{
    public const DEFAULT_SIZE = 64;

    public const DEFAULT_AVATAR = 'https://www.azuracast.com/img/avatar.png';

    public const SERVICE_LIBRAVATAR = 'libravatar';
    public const SERVICE_GRAVATAR = 'gravatar';
    public const SERVICE_DISABLED = 'disabled';

    public const DEFAULT_SERVICE = self::SERVICE_LIBRAVATAR;

    public function __construct(
        protected SettingsRepository $settingsRepo
    ) {
    }

    public function getAvatarService(): AvatarServiceInterface
    {
        $settings = $this->settingsRepo->readSettings();

        return match ($settings->getAvatarService()) {
            self::SERVICE_LIBRAVATAR => new Libravatar(),
            self::SERVICE_GRAVATAR => new Gravatar(),
            default => new Disabled()
        };
    }

    public function getAvatar(?string $email, int $size = self::DEFAULT_SIZE): string
    {
        $avatarService = $this->getAvatarService();

        $default = $this->settingsRepo->readSettings()->getAvatarDefaultUrl();

        if (empty($email)) {
            return $default;
        }

        return $avatarService->getAvatar($email, $size, $default);
    }
}
