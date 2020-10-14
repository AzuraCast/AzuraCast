<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180428062526 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_media (id INT AUTO_INCREMENT NOT NULL, playlist_id INT NOT NULL, media_id INT NOT NULL, weight SMALLINT NOT NULL, last_played INT NOT NULL, INDEX IDX_EA70D7796BBD148 (playlist_id), INDEX IDX_EA70D779EA9FDD75 (media_id), UNIQUE INDEX idx_playlist_media (playlist_id, media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_playlist_media ADD CONSTRAINT FK_EA70D7796BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_playlist_media ADD CONSTRAINT FK_EA70D779EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO station_playlist_media (playlist_id, media_id, weight, last_played) SELECT playlists_id, media_id, \'0\', \'0\' FROM station_playlist_has_media');

        $this->addSql('DROP TABLE station_playlist_has_media');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_has_media (media_id INT NOT NULL, playlists_id INT NOT NULL, INDEX IDX_668E6486EA9FDD75 (media_id), INDEX IDX_668E64869F70CF56 (playlists_id), PRIMARY KEY(media_id, playlists_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_playlist_has_media ADD CONSTRAINT FK_668E64869F70CF56 FOREIGN KEY (playlists_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_playlist_has_media ADD CONSTRAINT FK_668E6486EA9FDD75 FOREIGN KEY (media_id) REFERENCES station_media (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO station_playlist_has_media (playlists_id, media_id) SELECT playlist_id, media_id FROM station_playlist_media');

        $this->addSql('DROP TABLE station_playlist_media');
    }
}
