<?php

// phpcs:ignoreFile

declare(strict_types=1);

namespace App\Radio\Enums;

use App\Radio\Frontend\Icecast;
use App\Radio\Frontend\Remote;
use App\Radio\Frontend\Shoutcast;

enum AudioProcessingMethods: string
{
    case Liquidsoap = 'nrj';
    case StereoTool = 'stereo_tool';
    case None = 'none';

    public function getValue(): string
    {
        return $this->value;
    }

    public static function default(): self
    {
        return self::None;
    }
}
