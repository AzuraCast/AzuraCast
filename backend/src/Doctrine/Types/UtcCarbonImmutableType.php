<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use App\Utilities\Time;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class UtcCarbonImmutableType extends VarDateTimeImmutableType
{
    public const string DB_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CarbonImmutable
    {
        return Time::toNullableUtcCarbonImmutable($value);
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

        return (null === $value)
            ? $value
            : $value->format(self::DB_DATETIME_FORMAT);
    }
}
