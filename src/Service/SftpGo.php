<?php
namespace App\Service;

use App\Settings;

class SftpGo
{
    public static function isSupported(): bool
    {
        $settings = Settings::getInstance();

        return !$settings->isTesting() && $settings->isDockerRevisionNewerThan(7);
    }
}