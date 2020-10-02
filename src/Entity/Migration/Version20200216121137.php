<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200216121137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate to a shared schedules table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_schedules (id INT AUTO_INCREMENT NOT NULL, playlist_id INT DEFAULT NULL, streamer_id INT DEFAULT NULL, start_time SMALLINT NOT NULL, end_time SMALLINT NOT NULL, start_date VARCHAR(10) DEFAULT NULL, end_date VARCHAR(10) DEFAULT NULL, days VARCHAR(50) DEFAULT NULL, INDEX IDX_B3BFB2956BBD148 (playlist_id), INDEX IDX_B3BFB29525F432AD (streamer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_schedules ADD CONSTRAINT FK_B3BFB2956BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_schedules ADD CONSTRAINT FK_B3BFB29525F432AD FOREIGN KEY (streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO station_schedules (playlist_id, start_time, end_time, start_date, end_date, days) SELECT playlist_id, start_time, end_time, start_date, end_date, days FROM station_playlist_schedules');

        $this->addSql('DROP TABLE station_playlist_schedules');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_schedules (id INT AUTO_INCREMENT NOT NULL, playlist_id INT DEFAULT NULL, start_time SMALLINT NOT NULL, end_time SMALLINT NOT NULL, start_date VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, end_date VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, days VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX IDX_C61009BA6BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE station_playlist_schedules ADD CONSTRAINT FK_C61009BA6BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE station_schedules');
    }
}
