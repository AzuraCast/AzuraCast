<?php

declare(strict_types=1);

namespace App\Radio\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AudioProcessingMethods: string
{
    case None = 'none';
    case Liquidsoap = 'nrj';
    case MasterMe = 'master_me';
    case StereoTool = 'stereo_tool';

    public function getValue(): string
    {
        return $this->value;
    }

    public static function default(): self
    {
        return self::None;
    }
}
