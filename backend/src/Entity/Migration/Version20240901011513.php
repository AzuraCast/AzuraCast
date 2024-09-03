<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240901011513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track timestamp_scheduled and schedule_id in station_queue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD timestamp_scheduled INT, ADD schedule_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_timestamp_scheduled ON station_queue (timestamp_scheduled)');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES station_schedules (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_277B0055A40BC2D5 ON station_queue (schedule_id)');
        $this->addSQL('UPDATE station_queue SET timestamp_scheduled = timestamp_played');
        $this->addSQL('ALTER TABLE station_queue CHANGE timestamp_scheduled timestamp_scheduled INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY FK_277B0055A40BC2D5');
        $this->addSql('DROP INDEX IDX_277B0055A40BC2D5 ON station_queue');
        $this->addSql('DROP INDEX idx_timestamp_scheduled ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP timestamp_scheduled, DROP schedule_id');
    }
}
