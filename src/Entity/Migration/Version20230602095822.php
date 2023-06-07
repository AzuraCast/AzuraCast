<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

final class Version20230602095822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Audit Log changes from serialized PHP to JSON.';
    }

    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        // Clear records older than 6 months.
        $this->connection->executeQuery(
            <<<SQL
                DELETE FROM audit_log WHERE timestamp < (UNIX_TIMESTAMP() - (86400 * 180))
            SQL
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE changes changes LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);

        // Convert audit log changes to JSON.
        $allChanges = $this->connection->fetchAllAssociative(
            'SELECT id, changes FROM audit_log'
        );

        foreach ($allChanges as $row) {
            try {
                $newChanges = json_encode(
                    @unserialize($row['changes']),
                    JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
                );
            } catch (Exception) {
                $newChanges = null;
            }

            $this->connection->update(
                'audit_log',
                [
                    'changes' => $newChanges,
                ],
                [
                    'id' => $row['id'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE changes changes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }
}
