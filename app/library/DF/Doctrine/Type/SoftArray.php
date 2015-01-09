<?php
namespace DF\Doctrine\Type;

use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * "Soft Array" datatype - same as Array, but with silent failure.
 */
class SoftArray extends ArrayType
{
    const TYPENAME = 'array';
    
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if ($value === null)
            return null;

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        
        $val = @unserialize($value);
        return $val;
    }

    public function getName()
    {
        return self::TYPENAME;
    }
}