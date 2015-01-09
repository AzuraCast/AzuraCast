<?php
namespace DF\Doctrine\Type;

use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * My custom datatype.
 */
class Json extends ArrayType
{
    const TYPENAME = 'json';

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return json_encode($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null)
            return null;
        
        $value = (is_resource($value)) ? stream_get_contents($value, -1) : $value;
        
        return json_decode((string)$value, 1);
    }

    public function getName()
    {
        return self::TYPENAME;
    }
}