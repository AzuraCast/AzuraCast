<?php

declare(strict_types=1);

namespace App\Doctrine\Platform;

use Doctrine\DBAL\Platforms\MariaDB1010Platform;

class MariaDbPlatform extends MariaDB1010Platform
{
    public const int DEFAULT_DATETIME_PRECISION = 6;

    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        $precision = $column['precision'] ?? self::DEFAULT_DATETIME_PRECISION;

        if (isset($column['version']) && $column['version'] === true) {
            if ($precision) {
                return 'TIMESTAMP(' . $precision . ')';
            }

            return 'TIMESTAMP';
        }

        if ($precision) {
            return 'DATETIME(' . $precision . ')';
        }

        return 'DATETIME';
    }
}
