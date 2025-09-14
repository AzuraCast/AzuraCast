<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.20.4')]
final class Version20241123132944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Roll back scheduler changes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP request_priority, DROP requests_follow_format');
        $this->addSql('ALTER TABLE station_playlists DROP priority');
        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY FK_277B0055A40BC2D5');
        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY FK_277B005517421B18');
        $this->addSql('DROP INDEX IDX_277B005517421B18 ON station_queue');
        $this->addSql('DROP INDEX idx_timestamp_scheduled ON station_queue');
        $this->addSql('DROP INDEX IDX_277B0055A40BC2D5 ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP playlist_media_id, DROP timestamp_scheduled, DROP schedule_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station ADD request_priority SMALLINT DEFAULT NULL, ADD requests_follow_format TINYINT(1) DEFAULT 0 NOT NULL'
        );
        $this->addSql('ALTER TABLE station_playlists ADD priority SMALLINT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE station_queue ADD playlist_media_id INT DEFAULT NULL, ADD timestamp_scheduled INT NOT NULL, ADD schedule_id INT DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES station_schedules (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE station_queue ADD CONSTRAINT FK_277B005517421B18 FOREIGN KEY (playlist_media_id) REFERENCES station_playlist_media (id) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_277B005517421B18 ON station_queue (playlist_media_id)');
        $this->addSql('CREATE INDEX idx_timestamp_scheduled ON station_queue (timestamp_scheduled)');
        $this->addSql('CREATE INDEX IDX_277B0055A40BC2D5 ON station_queue (schedule_id)');
    }
}
