<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240901011513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_queue ADD timestamp_scheduled INT, ADD schedule_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_timestamp_scheduled ON station_queue (timestamp_scheduled)');
        $this->addSQL('UPDATE station_queue SET timestamp_scheduled = timestamp_played');
        $this->addSQL('ALTER TABLE station_queue CHANGE timestamp_scheduled timestamp_scheduled INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station CHANGE max_bitrate max_bitrate INT DEFAULT 0, CHANGE max_mounts max_mounts TINYINT(1) DEFAULT 0, CHANGE max_hls_streams max_hls_streams TINYINT(1) DEFAULT 0');
        $this->addSql('DROP INDEX idx_timestamp_scheduled ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP timestamp_scheduled, DROP schedule_id');
    }

}
