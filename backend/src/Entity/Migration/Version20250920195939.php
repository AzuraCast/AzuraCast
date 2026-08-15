<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250920195939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds playlist groups to station playlists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_group (playlist_id INT NOT NULL, playlist_group_id INT NOT NULL, weight INT NOT NULL, is_queued TINYINT(1) NOT NULL, last_played INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, INDEX IDX_ED9C32B06BBD148 (playlist_id), INDEX IDX_ED9C32B03891F2A (playlist_group_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE station_playlist_group ADD CONSTRAINT FK_ED9C32B06BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_playlist_group ADD CONSTRAINT FK_ED9C32B03891F2A FOREIGN KEY (playlist_group_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_group DROP FOREIGN KEY FK_ED9C32B06BBD148');
        $this->addSql('ALTER TABLE station_playlist_group DROP FOREIGN KEY FK_ED9C32B03891F2A');
        $this->addSql('DROP TABLE station_playlist_group');
    }
}
