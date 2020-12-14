<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201211164613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Improve indexing on audit log records and clean up spurious settings records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_search ON audit_log (class, user, identifier)');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->delete(
            'audit_log',
            [
                'class' => 'SettingsTable',
                'user' => null,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_search ON audit_log');
    }
}
