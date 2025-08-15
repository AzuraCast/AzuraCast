<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20250809232456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track whether media in a playlist is tied to a folder.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_media ADD folder_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_playlist_media ADD CONSTRAINT FK_EA70D779162CB942 FOREIGN KEY (folder_id) REFERENCES station_playlist_folders (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_EA70D779162CB942 ON station_playlist_media (folder_id)');
    }

    public function postUp(Schema $schema): void
    {
        $folders = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT * FROM station_playlist_folders
            SQL,
        );

        foreach ($folders as $row) {
            $this->connection->executeQuery(
                <<<SQL
                UPDATE station_playlist_media AS spm
                SET spm.folder_id = :folder_id
                WHERE spm.playlist_id = :playlist_id
                AND spm.media_id IN (
                    SELECT sm.id FROM station_media AS sm
                    WHERE sm.path LIKE :path
                )
                SQL,
                [
                    'folder_id' => $row['id'],
                    'playlist_id' => $row['playlist_id'],
                    'path' => $row['path'] . '/%',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_media DROP FOREIGN KEY FK_EA70D779162CB942');
        $this->addSql('DROP INDEX IDX_EA70D779162CB942 ON station_playlist_media');
        $this->addSql('ALTER TABLE station_playlist_media DROP folder_id');
    }
}
