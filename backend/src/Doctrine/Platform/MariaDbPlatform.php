<?php

declare(strict_types=1);

namespace App\Doctrine\Platform;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MariaDB1010Platform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Types\Type;

class MariaDbPlatform extends MariaDB1010Platform
{
    public const string DB_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function getDateTimeFormatString(): string
    {
        return self::DB_DATETIME_FORMAT;
    }

    public function getDateTimeTzFormatString(): string
    {
        return self::DB_DATETIME_FORMAT;
    }

    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        $precision = $column['precision'] ?? $column['length'] ?? 0;

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

    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new class ($connection, $this) extends MySQLSchemaManager {
            // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
            protected function _getPortableTableColumnDefinition(array $tableColumn): Column
            {
                $column = parent::_getPortableTableColumnDefinition($tableColumn);

                if ($tableColumn['type'] === 'datetime' && str_contains($tableColumn['COLUMN_TYPE'], '(')) {
                    // Parse the column spec to get precision.
                    $columnType = $tableColumn['COLUMN_TYPE'];

                    preg_match('/datetime\((\d+)\)/i', $columnType, $matches);

                    if ($matches[0]) {
                        $column->setType(Type::getType('datetime_immutable'));
                        $column->setPrecision((int)$matches[1]);
                    }
                }

                return $column;
            }
        };
    }
}
