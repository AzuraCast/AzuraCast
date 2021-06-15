<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create new dedicated table for remote relays.
 */
final class Version20180909035413 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE station_remotes (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, type VARCHAR(50) NOT NULL, enable_autodj TINYINT(1) NOT NULL, autodj_format VARCHAR(10) DEFAULT NULL, autodj_bitrate SMALLINT DEFAULT NULL, custom_listen_url VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, mount VARCHAR(150) DEFAULT NULL, source_username VARCHAR(100) DEFAULT NULL, source_password VARCHAR(100) DEFAULT NULL, INDEX IDX_779D0E8A21BDB235 (station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE station_remotes ADD CONSTRAINT FK_779D0E8A21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
    }

    public function postup(Schema $schema): void
    {
        $stations = $this->connection->fetchAllAssociative(
            "SELECT id, frontend_config FROM station WHERE frontend_type = 'remote'"
        );

        foreach ($stations as $station) {
            $mounts = $this->connection->fetchAllAssociative(
                'SELECT * FROM station_mounts WHERE station_id = ' . $station['id']
            );

            if (count($mounts) === 0) {
                $settings = json_decode($station['frontend_config'], true, 512, JSON_THROW_ON_ERROR);

                if (isset($settings['remote_type'])) {
                    $this->connection->insert('station_remotes', [
                        'station_id' => $station['id'],
                        'type' => $settings['remote_type'],
                        'url' => $settings['remote_url'],
                        'mount' => $settings['remote_mount'],
                        'enable_autodj' => 0,
                    ]);
                }
            } else {
                foreach ($mounts as $mount) {
                    $this->connection->insert('station_remotes', [
                        'station_id' => $station['id'],
                        'type' => $mount['remote_type'],
                        'url' => $mount['remote_url'],
                        'mount' => $mount['remote_mount'],
                        'custom_listen_url' => $mount['custom_listen_url'],
                        'enable_autodj' => (int)$mount['enable_autodj'],
                        'autodj_format' => $mount['autodj_format'],
                        'autodj_bitrate' => $mount['autodj_bitrate'],
                        'source_username' => $mount['remote_source_username'],
                        'source_password' => $mount['remote_source_password'],
                    ]);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE station_remotes');
    }
}
