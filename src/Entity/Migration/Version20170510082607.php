<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170510082607 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD radio_media_dir VARCHAR(255) DEFAULT NULL, ADD radio_playlists_dir VARCHAR(255) DEFAULT NULL, ADD radio_config_dir VARCHAR(255) DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM station') as $station) {
            $this->connection->update(
                'station',
                [
                    'radio_media_dir' => $station['radio_base_dir'] . '/media',
                    'radio_playlists_dir' => $station['radio_base_dir'] . '/playlists',
                    'radio_config_dir' => $station['radio_base_dir'] . '/config',
                ],
                [
                    'id' => $station['id'],
                ]
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP radio_media_dir, DROP radio_playlists_dir, DROP radio_config_dir');
    }
}
