<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.20.3')]
final class Version20241113155508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove "soft-delete" functionality from scheduler.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM station_queue WHERE is_cancelled = 1');
        $this->addSql('DROP INDEX idx_is_cancelled ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP is_cancelled');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD is_cancelled TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_is_cancelled ON station_queue (is_cancelled)');
    }
}
