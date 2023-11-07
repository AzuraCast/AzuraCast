<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity\Station;
use App\Environment;
use App\Utilities\File;

final class StereoTool
{
    public const VERSION_FILE = '.currentversion';

    public static function isInstalled(): bool
    {
        $libraryPath = self::getLibraryPath();

        return file_exists($libraryPath . '/' . self::VERSION_FILE)
            || file_exists($libraryPath . '/stereo_tool');
    }

    public static function getLibraryPath(): string
    {
        $parentDir = Environment::getInstance()->getParentDirectory();
        return File::getFirstExistingDirectory([
            $parentDir . '/storage/stereo_tool',
            $parentDir . '/servers/stereo_tool',
        ]);
    }

    public static function isReady(Station $station): bool
    {
        if (!self::isInstalled()) {
            return false;
        }

        $backendConfig = $station->getBackendConfig();
        return !empty($backendConfig->getStereoToolConfigurationPath());
    }

    public static function getVersion(): ?string
    {
        if (!self::isInstalled()) {
            return null;
        }

        $libraryPath = self::getLibraryPath();

        if (file_exists($libraryPath . '/' . self::VERSION_FILE)) {
            return file_get_contents($libraryPath . '/' . self::VERSION_FILE) . ' (Plugin)';
        }

        $binaryPath = $libraryPath . '/stereo_tool';

        return file_exists($binaryPath)
            ? 'Legacy CLI'
            : null;
    }
}
