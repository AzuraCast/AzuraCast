<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait UsesPropertyAccessor
{
    protected static ?PropertyAccessorInterface $propertyAccessor = null;

    protected static function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->disableExceptionOnInvalidIndex()
                ->disableExceptionOnInvalidPropertyPath()
                ->disableMagicMethods()
                ->getPropertyAccessor();
        }

        return self::$propertyAccessor;
    }
}
