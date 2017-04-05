<?php
namespace App\Doctrine\Type;

use Doctrine\DBAL\Types\ArrayType;

/**
 * "Soft Array" datatype - same as Array, but with silent failure.
 */
class SoftArray extends ArrayType
{
    const TYPENAME = 'array';

    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        $val = @unserialize($value);

        return $val;
    }

    public function getName()
    {
        return self::TYPENAME;
    }
}