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
        return $this->isInstalled() && !empty($station->getStereoToolConfigurationPath());
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

        $outputLines = explode(PHP_EOL, $process->getErrorOutput());
        $version = explode(' - ', $outputLines[2])[0];

        return $version;
    }
}
