<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200816092130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create new "station_queue" table.';
    }

    public function preUp(Schema $schema): void
    {
        $this->connection->executeStatement('DELETE FROM song_history WHERE timestamp_start = 0');
        $this->connection->executeStatement('UPDATE station SET nowplaying=null, nowplaying_timestamp=0');
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS station_queue (id INT AUTO_INCREMENT NOT NULL, song_id VARCHAR(50) NOT NULL, station_id INT DEFAULT NULL, playlist_id INT DEFAULT NULL, media_id INT DEFAULT NULL, request_id INT DEFAULT NULL, sent_to_autodj TINYINT(1) NOT NULL, autodj_custom_uri VARCHAR(255) DEFAULT NULL, timestamp_cued INT NOT NULL, duration INT DEFAULT NULL, INDEX IDX_277B0055A0BDB2F3 (song_id), INDEX IDX_277B005521BDB235 (station_id), INDEX IDX_277B00556BBD148 (playlist_id), INDEX IDX_277B0055EA9FDD75 (media_id), INDEX IDX_277B0055427EB8A5 (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055A0BDB2F3 FOREIGN KEY IF NOT EXISTS (song_id) REFERENCES songs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B005521BDB235 FOREIGN KEY IF NOT EXISTS (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B00556BBD148 FOREIGN KEY IF NOT EXISTS (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055EA9FDD75 FOREIGN KEY IF NOT EXISTS (media_id) REFERENCES station_media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055427EB8A5 FOREIGN KEY IF NOT EXISTS (request_id) REFERENCES station_requests (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_timestamp_cued ON song_history');
        $this->addSql('ALTER TABLE song_history DROP timestamp_cued, DROP sent_to_autodj, DROP autodj_custom_uri');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE station_queue');
        $this->addSql('ALTER TABLE song_history ADD timestamp_cued INT DEFAULT NULL, ADD sent_to_autodj TINYINT(1) NOT NULL, ADD autodj_custom_uri VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`');
        $this->addSql('CREATE INDEX idx_timestamp_cued ON song_history (timestamp_cued)');
    }
}
