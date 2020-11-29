<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190429040410 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP interrupt_other_songs, DROP loop_playlist_once, DROP play_single_track');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD interrupt_other_songs TINYINT(1) NOT NULL, ADD loop_playlist_once TINYINT(1) NOT NULL, ADD play_single_track TINYINT(1) NOT NULL');
    }
}
