<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260806020647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a covering index to song_history for the analytics stats-by-time-range query.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE INDEX idx_station_timestamps ON song_history (station_id, timestamp_end, timestamp_start)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_station_timestamps ON song_history');
    }
}
