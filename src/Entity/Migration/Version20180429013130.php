<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180429013130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists ADD playback_order VARCHAR(50) NOT NULL, ADD remote_url VARCHAR(255) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->update('station_playlists', [
            'source' => 'songs',
            'playback_order' => 'random',
        ], [1 => 1]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_playlists DROP playback_order, DROP remote_url');
    }
}
