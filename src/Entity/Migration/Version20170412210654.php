<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add relay URL to mountpoint table, update Shoutcast 2 stations to have one default mount point.
 */
final class Version20170412210654 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts ADD relay_url VARCHAR(255) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        $allStations = $this->connection->fetchAllAssociative(
            "SELECT * FROM station WHERE frontend_type='shoutcast2'"
        );

        foreach ($allStations as $station) {
            $this->connection->insert('station_mounts', [
                'station_id' => $station['id'],
                'name' => '/radio.mp3',
                'is_default' => 1,
                'fallback_mount' => '/autodj.mp3',
                'enable_streamers' => 1,
                'enable_autodj' => 1,
                'autodj_format' => 'mp3',
                'autodj_bitrate' => 128,
            ]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP relay_url');
    }
}
