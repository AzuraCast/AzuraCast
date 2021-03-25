<?php

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

    protected SettingsRepository $settingsRepo;

    public function __construct(SettingsRepository $settingsRepo)
    {
        $this->settingsRepo = $settingsRepo;
    }

    public function getAvatarService(): AvatarServiceInterface
    {
        $settings = $this->settingsRepo->readSettings();

        switch ($settings->getAvatarService()) {
            case self::SERVICE_LIBRAVATAR:
                return new Libravatar();

            case self::SERVICE_GRAVATAR:
                return new Gravatar();

            case self::SERVICE_DISABLED:
            default:
                return new Disabled();
        }
    }

    public function getAvatar(string $email, int $size = self::DEFAULT_SIZE): string
    {
        $avatarService = $this->getAvatarService();

        $settings = $this->settingsRepo->readSettings();
        $default = $settings->getAvatarDefaultUrl();

        return $avatarService->getAvatar($email, $size, $default);
    }
}
