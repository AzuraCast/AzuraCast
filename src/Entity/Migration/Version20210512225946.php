<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210512225946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial Podcast table setup.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE podcast (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', storage_location_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, language VARCHAR(2) NOT NULL, art_updated_at INT NOT NULL, INDEX IDX_D7E805BDCDDD8AF (storage_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE podcast_category (id INT AUTO_INCREMENT NOT NULL, podcast_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', category VARCHAR(255) NOT NULL, INDEX IDX_E633B1E8786136AB (podcast_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE podcast_episode (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', podcast_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, publish_at INT DEFAULT NULL, explicit TINYINT(1) NOT NULL, created_at INT NOT NULL, art_updated_at INT NOT NULL, INDEX IDX_77EB2BD0786136AB (podcast_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE podcast_media (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', storage_location_id INT DEFAULT NULL, episode_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', original_name VARCHAR(200) NOT NULL, length NUMERIC(7, 2) NOT NULL, length_text VARCHAR(10) NOT NULL, path VARCHAR(500) NOT NULL, mime_type VARCHAR(255) NOT NULL, modified_time INT NOT NULL, art_updated_at INT NOT NULL, INDEX IDX_15AD8829CDDD8AF (storage_location_id), UNIQUE INDEX UNIQ_15AD8829362B62A0 (episode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BDCDDD8AF FOREIGN KEY (storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE podcast_category ADD CONSTRAINT FK_E633B1E8786136AB FOREIGN KEY (podcast_id) REFERENCES podcast (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE podcast_episode ADD CONSTRAINT FK_77EB2BD0786136AB FOREIGN KEY (podcast_id) REFERENCES podcast (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE podcast_media ADD CONSTRAINT FK_15AD8829CDDD8AF FOREIGN KEY (storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE podcast_media ADD CONSTRAINT FK_15AD8829362B62A0 FOREIGN KEY (episode_id) REFERENCES podcast_episode (id) ON DELETE SET NULL'
        );
        $this->addSql('ALTER TABLE station ADD podcasts_storage_location_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE station ADD CONSTRAINT FK_9F39F8B123303CD0 FOREIGN KEY (podcasts_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_9F39F8B123303CD0 ON station (podcasts_storage_location_id)');
    }

    public function postUp(Schema $schema): void
    {
        $stations = $this->connection->fetchAllAssociative(
            'SELECT id, radio_base_dir FROM station WHERE podcasts_storage_location_id IS NULL ORDER BY id ASC'
        );

        foreach ($stations as $row) {
            $stationId = $row['id'];

            $baseDir = $row['radio_base_dir'];

            $this->connection->insert(
                'storage_location',
                [
                    'type' => 'station_podcasts',
                    'adapter' => 'local',
                    'path' => $baseDir . '/podcasts',
                    'storage_quota' => null,
                ]
            );

            $podcastsStorageLocationId = $this->connection->lastInsertId('storage_location');

            $this->connection->update(
                'station',
                [
                    'podcasts_storage_location_id' => $podcastsStorageLocationId,
                ],
                [
                    'id' => $stationId,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast_category DROP FOREIGN KEY FK_E633B1E8786136AB');
        $this->addSql('ALTER TABLE podcast_episode DROP FOREIGN KEY FK_77EB2BD0786136AB');
        $this->addSql('ALTER TABLE podcast_media DROP FOREIGN KEY FK_15AD8829362B62A0');
        $this->addSql('DROP TABLE podcast');
        $this->addSql('DROP TABLE podcast_category');
        $this->addSql('DROP TABLE podcast_episode');
        $this->addSql('DROP TABLE podcast_media');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B123303CD0');
        $this->addSql('DROP INDEX IDX_9F39F8B123303CD0 ON station');
        $this->addSql('ALTER TABLE station DROP podcasts_storage_location_id');
    }
}
