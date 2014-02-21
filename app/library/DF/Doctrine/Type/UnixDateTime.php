<?php
namespace DF\Doctrine\Type;

use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * "UNIX Timestamp Date/Time" datatype - same as DateTime, but stored as an integer (for BC)
 */
class UnixDateTime extends IntegerType
{
	const TYPENAME = 'unixdatetime';
	
	public function getName()
	{
		return self::TYPENAME;
	}
	
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
		if ($value !== NULL)
		{
			if ($value instanceof \DateTime)
				return $value->getTimestamp();
			else
				return (int)$value;
		}
		return NULL;
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
		if ((int)$value)
			return \DateTime::createFromFormat(\DateTime::ISO8601, date(\DateTime::ISO8601, (int)$value));
		else
			return NULL;
    }
}