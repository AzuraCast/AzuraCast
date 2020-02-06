<?php
/**
 * Custom MySQL platform that supports utf8mb4 charset and encoding by default for tables.
 */

namespace App\Doctrine\Platform;

use Doctrine\DBAL\Platforms\MySqlPlatform;

class MysqlUnicode extends MySqlPlatform
{
    protected function _getCreateTableSQL($tableName, array $columns, array $options = [])
    {
        if (!isset($options['charset'])) {
            $options['charset'] = 'utf8mb4';
        }

        if (!isset($options['collate'])) {
            $options['collate'] = 'utf8mb4_unicode_ci';
        }

        return parent::_getCreateTableSQL($tableName, $columns, $options);
    }
}