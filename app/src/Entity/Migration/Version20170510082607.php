<?php

namespace Entity\Migration;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170510082607 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station ADD radio_media_dir VARCHAR(255) DEFAULT NULL, ADD radio_playlists_dir VARCHAR(255) DEFAULT NULL, ADD radio_config_dir VARCHAR(255) DEFAULT NULL');
    }

    public function postUp(Schema $schema)
    {
        $all_stations = $this->connection->fetchAll("SELECT * FROM station");

        foreach ($all_stations as $station) {
            $this->connection->update('station', [
                'radio_media_dir' => $station['radio_base_dir'].'/media',
                'radio_playlists_dir' => $station['radio_base_dir'].'/playlists',
                'radio_config_dir' => $station['radio_base_dir'].'/config',
            ], [
                'id' => $station['id'],
            ]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station DROP radio_media_dir, DROP radio_playlists_dir, DROP radio_config_dir');
    }
}
