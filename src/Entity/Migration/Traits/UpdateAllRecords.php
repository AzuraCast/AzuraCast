<?php

declare(strict_types=1);

namespace App\Entity\Migration\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

trait UpdateAllRecords
{
    /** @var Connection */
    protected $connection;

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $table Table name
     * @param array<string, mixed> $data Column-value pairs
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function updateAllRecords(
        string $table,
        array $data,
        array $types = []
    ): int|string {
        $columns = $values = $set = [];

        foreach ($data as $columnName => $value) {
            $columns[] = $columnName;
            $values[] = $value;
            $set[] = $columnName . ' = ?';
        }

        if (is_string(key($types))) {
            $types = $this->extractTypeValues($columns, $types);
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $set);
        return $this->connection->executeStatement($sql, $values, $types);
    }

    /**
     * Extract ordered type list from an ordered column list and type map.
     *
     * @param array<int, string> $columnList
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     *
     * @return array<int, int|string|Type|null>|array<string, int|string|Type|null>
     */
    private function extractTypeValues(array $columnList, array $types): array
    {
        $typeValues = [];

        foreach ($columnList as $columnName) {
            $typeValues[] = $types[$columnName] ?? ParameterType::STRING;
        }

        return $typeValues;
    }
}
