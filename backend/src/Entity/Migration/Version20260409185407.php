<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260409185407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add clockwheel playlist support.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_playlist_child (parent_playlist_id INT NOT NULL, child_playlist_id INT DEFAULT NULL, position SMALLINT NOT NULL, song_count SMALLINT NOT NULL, request_mode VARCHAR(20) NOT NULL, id INT AUTO_INCREMENT NOT NULL, INDEX IDX_A2EF225C6D97C338 (parent_playlist_id), INDEX IDX_A2EF225CDC3FB666 (child_playlist_id), UNIQUE INDEX idx_parent_position (parent_playlist_id, position), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE station_playlist_child ADD CONSTRAINT FK_A2EF225C6D97C338 FOREIGN KEY (parent_playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station_playlist_child ADD CONSTRAINT FK_A2EF225CDC3FB666 FOREIGN KEY (child_playlist_id) REFERENCES station_playlists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_history ADD clockwheel_step SMALLINT DEFAULT NULL, ADD clockwheel_playlist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164C6D69377 FOREIGN KEY (clockwheel_playlist_id) REFERENCES station_playlists (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2AD16164C6D69377 ON song_history (clockwheel_playlist_id)');
        $this->addSql('ALTER TABLE station_playlists ADD clockwheel_step SMALLINT NOT NULL, ADD clockwheel_songs_played SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE station_queue ADD clockwheel_step SMALLINT DEFAULT NULL, ADD clockwheel_playlist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_queue ADD CONSTRAINT FK_277B0055C6D69377 FOREIGN KEY (clockwheel_playlist_id) REFERENCES station_playlists (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_277B0055C6D69377 ON station_queue (clockwheel_playlist_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlist_child DROP FOREIGN KEY FK_A2EF225C6D97C338');
        $this->addSql('ALTER TABLE station_playlist_child DROP FOREIGN KEY FK_A2EF225CDC3FB666');
        $this->addSql('DROP TABLE station_playlist_child');
        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164C6D69377');
        $this->addSql('DROP INDEX IDX_2AD16164C6D69377 ON song_history');
        $this->addSql('ALTER TABLE song_history DROP clockwheel_step, DROP clockwheel_playlist_id');
        $this->addSql('ALTER TABLE station_playlists DROP clockwheel_step, DROP clockwheel_songs_played');
        $this->addSql('ALTER TABLE station_queue DROP FOREIGN KEY FK_277B0055C6D69377');
        $this->addSql('DROP INDEX IDX_277B0055C6D69377 ON station_queue');
        $this->addSql('ALTER TABLE station_queue DROP clockwheel_step, DROP clockwheel_playlist_id');
    }
}
