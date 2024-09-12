<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class StationMaxBitrateChecker extends Constraint
{
    public function __construct(
        public string $stationGetter = '',
        public string|array $selectedBitrate = '',
        array $options = []
    ) {
        $options['stationGetter'] = $stationGetter;
        $options['selectedBitrate'] = $selectedBitrate;

        parent::__construct($options);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['stationGetter', 'selectedBitrate'];
    }
}
