<?php

declare(strict_types=1);

namespace App\Radio;

use App\Environment;
use RuntimeException;
use Symfony\Component\Process\Process;
use App\Entity\Station;

final class StereoTool
{
    public static function isInstalled(): bool
    {
        return file_exists(self::getBinaryPath());
    }

    public static function getBinaryPath(): string
    {
        $environment = Environment::getInstance();
        return $environment->getParentDirectory() . '/servers/stereo_tool/stereo_tool';
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

        $binaryPath = self::getBinaryPath();

        $process = new Process([$binaryPath, '--help']);
        $process->setWorkingDirectory(dirname($binaryPath));
        $process->setTimeout(5.0);

        try {
            $process->run();
        } catch (RuntimeException) {
            return null;
        }

        if (!$process->isSuccessful()) {
            return null;
        }

        preg_match('/STEREO TOOL ([.\d]+) CONSOLE APPLICATION/i', $process->getErrorOutput(), $matches);
        return $matches[1] ?? null;
    }
}
