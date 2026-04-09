<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260409140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allow_requests flag and make child_playlist_id nullable for request slot steps.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD allow_requests TINYINT(1) NOT NULL DEFAULT 0'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'DROP FOREIGN KEY FK_child_playlist'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'MODIFY child_playlist_id INT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD CONSTRAINT FK_child_playlist FOREIGN KEY (child_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM station_playlist_child WHERE child_playlist_id IS NULL'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'DROP FOREIGN KEY FK_child_playlist'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'MODIFY child_playlist_id INT NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD CONSTRAINT FK_child_playlist FOREIGN KEY (child_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'DROP allow_requests'
        );
    }
}
