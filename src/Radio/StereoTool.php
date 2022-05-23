<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Environment;
use Symfony\Component\Process\Process;

class StereoTool
{
    public function __construct(
        protected Environment $environment,
    ) {
    }

    public function isInstalled(): bool
    {
        return file_exists($this->getBinaryPath());
    }

    public function getBinaryPath(): string
    {
        return $this->environment->getParentDirectory() . '/servers/stereo_tool/stereo_tool';
    }

    public function isReady(Entity\Station $station): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        $backendConfig = $station->getBackendConfig();
        return !empty($backendConfig->getStereoToolConfigurationPath());
    }

    public function getVersion(): ?string
    {
        if (!$this->isInstalled()) {
            return null;
        }

        $binaryPath = $this->getBinaryPath();

        $process = new Process([$binaryPath, '--help']);
        $process->setWorkingDirectory(dirname($binaryPath));
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        preg_match('/STEREO TOOL ([.\d]+) CONSOLE APPLICATION/i', $process->getErrorOutput(), $matches);
        return $matches[1] ?? null;
    }
}
