<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20220405031647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split indices up in station_queue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_cued_status ON station_queue');
        $this->addSql('DROP INDEX idx_played_status ON station_queue');

        $this->addSql('CREATE INDEX idx_is_played ON station_queue (is_played)');
        $this->addSql('CREATE INDEX idx_timestamp_played ON station_queue (timestamp_played)');
        $this->addSql('CREATE INDEX idx_sent_to_autodj ON station_queue (sent_to_autodj)');
        $this->addSql('CREATE INDEX idx_timestamp_cued ON station_queue (timestamp_cued)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_is_played ON station_queue');
        $this->addSql('DROP INDEX idx_timestamp_played ON station_queue');
        $this->addSql('DROP INDEX idx_sent_to_autodj ON station_queue');
        $this->addSql('DROP INDEX idx_timestamp_cued ON station_queue');
        $this->addSql('CREATE INDEX idx_cued_status ON station_queue (sent_to_autodj, timestamp_cued)');
        $this->addSql('CREATE INDEX idx_played_status ON station_queue (is_played, timestamp_played)');
    }
}
