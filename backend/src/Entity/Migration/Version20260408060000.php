<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.23.5')]
#[StableMigration('0.23.6')]
final class Version20260408060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add covering indexes to listener and song_history for analytics and API query performance.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_timestamp_start ON listener (timestamp_start)');
        $this->addSql('CREATE INDEX idx_station_timestamps_hash ON listener (station_id, timestamp_end, timestamp_start, listener_hash)');
        $this->addSql('CREATE INDEX idx_station_visible_id ON song_history (station_id, is_visible, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_timestamp_start ON listener');
        $this->addSql('DROP INDEX idx_station_timestamps_hash ON listener');
        $this->addSql('DROP INDEX idx_station_visible_id ON song_history');
    }
}
