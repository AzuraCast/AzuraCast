<?php
namespace DF\Doctrine\Type;

use \Doctrine\DBAL\Types\DateTimeType;
use \Doctrine\DBAL\Platforms\AbstractPlatform;
use \Doctrine\DBAL\Types\ConversionException;

class UTCDateTime extends DateTimeType
{
    static private $utc = null;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $utc = (self::$utc) ? self::$utc : (self::$utc = new \DateTimeZone('UTC'));
        $value->setTimezone($utc);

        return $value->format($platform->getDateTimeFormatString());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $val = \DateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            (self::$utc) ? self::$utc : (self::$utc = new \DateTimeZone('UTC'))
        );

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
        return $val;
    }
}