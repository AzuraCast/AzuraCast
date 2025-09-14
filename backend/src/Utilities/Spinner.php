<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Traits\AvailableStaticallyTrait;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

final class Spinner extends ProgressIndicator
{
    use AvailableStaticallyTrait;

    public const array DEFAULT_FRAMES = [
        '🖥️🎶－🎵－📻',
        '🖥️－🎶－🎵📻',
        '🖥️🎵－🎶－📻',
        '🖥️－🎵－🎶📻',
    ];

    public function __construct(
        OutputInterface $output,
        ?string $format = null,
        int $indicatorChangeInterval = 100,
        ?array $indicatorValues = null,
        ?string $finishedIndicatorValue = null
    ) {
        $indicatorValues ??= self::DEFAULT_FRAMES;

        parent::__construct($output, $format, $indicatorChangeInterval, $indicatorValues, $finishedIndicatorValue);
    }
}
