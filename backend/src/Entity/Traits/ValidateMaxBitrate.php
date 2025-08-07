<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait ValidateMaxBitrate
{
    private function doValidateMaxBitrate(
        ExecutionContextInterface $context,
        int $maxBitrate,
        ?int $bitrate = null,
        ?string $validatePath = null,
    ): void {
        if ($maxBitrate === 0) {
            return;
        }

        $bitrate ??= 0;

        if ($bitrate > $maxBitrate) {
            $message = __(
                'The selected bitrate: %selected_bitrate%, is higher than the ' .
                'station\'s bitrate limit:  %station_limit%'
            );

            $violation = $context->buildViolation($message)
                ->setParameter('%selected_bitrate%', (string)$bitrate)
                ->setParameter('%station_limit%', (string)$maxBitrate);

            if ($validatePath !== null) {
                $violation = $violation->atPath($validatePath);
            }

            $violation->addViolation();
        }
    }
}
