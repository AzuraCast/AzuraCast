<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250707074157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new "delete station media" permission.';
    }

    public function up(Schema $schema): void
    {
        $permissions = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT role_id, station_id FROM role_permissions
            WHERE action_name = :name
            SQL,
            [
                'name' => 'manage station media',
            ]
        );

        foreach ($permissions as $row) {
            $this->addSql(
                <<<'SQL'
                INSERT INTO role_permissions
                SET role_id=:role_id, station_id=:station_id,
                    action_name=:action_name
                SQL,
                [
                    'role_id' => $row['role_id'],
                    'station_id' => $row['station_id'] ?? null,
                    'action_name' => 'delete station media',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            DELETE FROM role_permissions
            WHERE action_name = :action_name
            SQL,
            [
                'action_name' => 'delete station media',
            ]
        );
    }
}
