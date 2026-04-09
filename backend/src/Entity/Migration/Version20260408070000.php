<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260408070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add clockwheel playlist support: station_playlist_child table and clockwheel state fields on station_playlists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE station_playlist_child ('
            . 'id INT AUTO_INCREMENT NOT NULL, '
            . 'parent_playlist_id INT NOT NULL, '
            . 'child_playlist_id INT NOT NULL, '
            . 'position SMALLINT NOT NULL DEFAULT 0, '
            . 'song_count SMALLINT NOT NULL DEFAULT 1, '
            . 'INDEX IDX_parent_playlist (parent_playlist_id), '
            . 'INDEX IDX_child_playlist (child_playlist_id), '
            . 'UNIQUE INDEX idx_parent_position (parent_playlist_id, position), '
            . 'PRIMARY KEY(id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD CONSTRAINT FK_parent_playlist FOREIGN KEY (parent_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE station_playlist_child '
            . 'ADD CONSTRAINT FK_child_playlist FOREIGN KEY (child_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE station_playlists '
            . 'ADD clockwheel_step SMALLINT NOT NULL DEFAULT 0, '
            . 'ADD clockwheel_songs_played SMALLINT NOT NULL DEFAULT 0'
        );

        $this->addSql(
            'ALTER TABLE station_queue '
            . 'ADD clockwheel_playlist_id INT DEFAULT NULL, '
            . 'ADD clockwheel_step SMALLINT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE station_queue '
            . 'ADD CONSTRAINT FK_queue_clockwheel FOREIGN KEY (clockwheel_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'ALTER TABLE song_history '
            . 'ADD clockwheel_playlist_id INT DEFAULT NULL, '
            . 'ADD clockwheel_step SMALLINT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE song_history '
            . 'ADD CONSTRAINT FK_history_clockwheel FOREIGN KEY (clockwheel_playlist_id) '
            . 'REFERENCES station_playlists (id) ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_playlist_child');
        $this->addSql(
            'ALTER TABLE station_playlists '
            . 'DROP clockwheel_step, '
            . 'DROP clockwheel_songs_played'
        );
        $this->addSql(
            'ALTER TABLE station_queue '
            . 'DROP FOREIGN KEY FK_queue_clockwheel'
        );
        $this->addSql(
            'ALTER TABLE station_queue '
            . 'DROP clockwheel_playlist_id, '
            . 'DROP clockwheel_step'
        );
        $this->addSql(
            'ALTER TABLE song_history '
            . 'DROP FOREIGN KEY FK_history_clockwheel'
        );
        $this->addSql(
            'ALTER TABLE song_history '
            . 'DROP clockwheel_playlist_id, '
            . 'DROP clockwheel_step'
        );
    }
}
