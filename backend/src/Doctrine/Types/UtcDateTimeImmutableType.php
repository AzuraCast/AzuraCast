<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class UtcDateTimeImmutableType extends VarDateTimeImmutableType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        $value = parent::convertToPHPValue($value, $platform);
        return $value?->setTimezone(Time::getUtc());
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        try {
            $value = Time::toNullableUtcCarbonImmutable($value);
        } catch (ValueNotConvertible) {
            throw InvalidType::new(
                $value,
                static::class,
                ['null', 'DateTime', 'Carbon']
            );
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
