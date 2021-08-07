<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170510091820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD radio_base_dir VARCHAR(255) DEFAULT NULL, DROP radio_playlists_dir, DROP radio_config_dir');
    }

    public function postup(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM station') as $station) {
            $this->connection->update(
                'station',
                [
                    'radio_base_dir' => str_replace('/media', '', $station['radio_media_dir']),
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
        $this->addSql('ALTER TABLE station ADD radio_config_dir VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE radio_base_dir radio_playlists_dir VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
