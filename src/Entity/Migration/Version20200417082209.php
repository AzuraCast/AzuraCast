<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200417082209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand indices for improved performance.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX update_idx ON listener');
        $this->addSql('DROP INDEX search_idx ON listener');
        $this->addSql('CREATE INDEX idx_timestamps ON listener (timestamp_end, timestamp_start)');

        $this->addSql('DROP INDEX history_idx ON song_history');
        $this->addSql('CREATE INDEX idx_timestamp_cued ON song_history (timestamp_cued)');
        $this->addSql('CREATE INDEX idx_timestamp_start ON song_history (timestamp_start)');
        $this->addSql('CREATE INDEX idx_timestamp_end ON song_history (timestamp_end)');

        $this->addSql('CREATE INDEX idx_short_name ON station (short_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_timestamps ON listener');
        $this->addSql('CREATE INDEX update_idx ON listener (listener_hash)');
        $this->addSql('CREATE INDEX search_idx ON listener (listener_uid, timestamp_end)');
        $this->addSql('DROP INDEX idx_timestamp_cued ON song_history');
        $this->addSql('DROP INDEX idx_timestamp_start ON song_history');
        $this->addSql('DROP INDEX idx_timestamp_end ON song_history');
        $this->addSql('CREATE INDEX history_idx ON song_history (timestamp_start, timestamp_end, listeners_start)');
        $this->addSql('DROP INDEX idx_short_name ON station');
    }
}
