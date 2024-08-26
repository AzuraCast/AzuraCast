<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class StationMaxBitrateCheckerValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StationMaxBitrateChecker) {
            throw new UnexpectedTypeException($constraint, StationMaxBitrateChecker::class);
        }

        $stationGetter = $constraint->stationGetter;
        $selectedBitrateProperty = $constraint->selectedBitrate;

        $station = ($stationGetter === 'self') ?
            $this->context->getObject() :
            $this->context->getObject()->{'get' . $stationGetter}();

        $stationMaxBitrate = $station->getMaxBitrate();

        if ($stationMaxBitrate === 0) {
            return;
        }

        if (is_array($selectedBitrateProperty)) {
            $selectedBitrate = $this->context->getObject();
            foreach ($selectedBitrateProperty as $value) {
                $selectedBitrate = $selectedBitrate->{'get' . ucfirst($value)}();
            }
        } else {
            $selectedBitrate = $this->context->getObject()->{'get' . ucfirst($selectedBitrateProperty)}();
        }

        $message = __(
            'The selected bitrate: %selected_bitrate%, is higher than the station\'s bitrate limit:  %station_limit%'
        );

        if ($selectedBitrate > $stationMaxBitrate) {
            $this->context->buildViolation($message)
                ->setParameter('%selected_bitrate%', (string) $selectedBitrate)
                ->setParameter('%station_limit%', (string) $stationMaxBitrate)
                ->addViolation();
        }
    }
}
