<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Normalizer;

use App\Utilities\Time;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class DateTimeNormalizer implements NormalizerInterface
{
    public function __construct(
        private string $format = Time::JS_ISO8601_FORMAT
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DateTimeInterface::class => true,
            DateTime::class => true,
            DateTimeImmutable::class => true,
            CarbonInterface::class => true,
            Carbon::class => true,
            CarbonImmutable::class => true,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DateTimeInterface;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        if (!$data instanceof DateTimeInterface) {
            throw new InvalidArgumentException('The data must be a DateTimeInterface.');
        }

        // Ensure output is UTC
        if (0 !== $data->getOffset()) {
            if (!$data instanceof DateTimeImmutable) {
                $data = CarbonImmutable::instance($data);
            }

            $data = $data->setTimezone(Time::getUtc());
        }

        return $data->format($this->format);
    }
}
