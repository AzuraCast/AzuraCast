<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211124165404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "timestamp_played" to queue items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD timestamp_played INT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery(
            'UPDATE station_queue SET timestamp_played=timestamp_cued WHERE timestamp_played IS NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue DROP timestamp_played');
    }
}
