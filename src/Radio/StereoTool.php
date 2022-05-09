<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Environment;

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
}
