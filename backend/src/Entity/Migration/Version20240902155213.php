<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240902155213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_cancelled field to station_queue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD is_cancelled TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_is_cancelled ON station_queue (is_cancelled)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_is_cancelled ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP is_cancelled');
    }
}
