<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190326051220 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD play_per_hour_minute SMALLINT NOT NULL, ADD interrupt_other_songs TINYINT(1) NOT NULL, ADD loop_playlist_once TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP play_per_hour_minute, DROP interrupt_other_songs, DROP loop_playlist_once');
    }
}
