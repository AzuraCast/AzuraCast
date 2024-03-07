<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240221151753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ability for podcasts to sync from playlists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast ADD playlist_id INT DEFAULT NULL, ADD source VARCHAR(50) NOT NULL, ADD playlist_auto_publish TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BD6BBD148 FOREIGN KEY (playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D7E805BD6BBD148 ON podcast (playlist_id)');
        $this->addSql('ALTER TABLE podcast_episode ADD playlist_media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE podcast_episode ADD CONSTRAINT FK_77EB2BD017421B18 FOREIGN KEY (playlist_media_id) REFERENCES station_media (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_77EB2BD017421B18 ON podcast_episode (playlist_media_id)');

        $this->addSql(
            <<<'SQL'
                UPDATE podcast SET source='manual'
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast DROP FOREIGN KEY FK_D7E805BD6BBD148');
        $this->addSql('DROP INDEX IDX_D7E805BD6BBD148 ON podcast');
        $this->addSql('ALTER TABLE podcast DROP playlist_id, DROP source, DROP playlist_auto_publish');
        $this->addSql('ALTER TABLE podcast_episode DROP FOREIGN KEY FK_77EB2BD017421B18');
        $this->addSql('DROP INDEX UNIQ_77EB2BD017421B18 ON podcast_episode');
        $this->addSql('ALTER TABLE podcast_episode DROP playlist_media_id');
    }
}
