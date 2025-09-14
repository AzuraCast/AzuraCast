<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.22.0')]
#[StableMigration('0.22.1')]
final class Version20250718124622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prevent nullable values in several fields.';
    }

    public function preUp(Schema $schema): void
    {
        $this->connection->executeQuery(
            <<<'SQL'
                DELETE FROM station WHERE radio_base_dir IS NULL
            SQL
        );

        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station SET timezone='UTC' WHERE timezone IS NULL
            SQL
        );

        $this->connection->executeQuery(
            <<<'SQL'
                DELETE FROM station WHERE media_storage_location_id IS NULL
                    OR recordings_storage_location_id IS NULL
                    OR podcasts_storage_location_id IS NULL
            SQL
        );

        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station_mounts SET display_name='' WHERE display_name IS NULL
            SQL
        );

        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station_remotes SET display_name='' WHERE display_name IS NULL
            SQL
        );

        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station_streamers SET display_name='' WHERE display_name IS NULL
            SQL
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B123303CD0');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B15C7361BE');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1C896ABC5');

        $this->addSql('ALTER TABLE station CHANGE radio_base_dir radio_base_dir VARCHAR(255) NOT NULL, CHANGE timezone timezone VARCHAR(100) NOT NULL, CHANGE media_storage_location_id media_storage_location_id INT NOT NULL, CHANGE recordings_storage_location_id recordings_storage_location_id INT NOT NULL, CHANGE podcasts_storage_location_id podcasts_storage_location_id INT NOT NULL');

        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B123303CD0 FOREIGN KEY (podcasts_storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B15C7361BE FOREIGN KEY (recordings_storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1C896ABC5 FOREIGN KEY (media_storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE station_mounts CHANGE display_name display_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE station_remotes CHANGE display_name display_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE station_streamers CHANGE display_name display_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1C896ABC5');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B15C7361BE');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B123303CD0');

        $this->addSql('ALTER TABLE station CHANGE radio_base_dir radio_base_dir VARCHAR(255) DEFAULT NULL, CHANGE timezone timezone VARCHAR(100) DEFAULT NULL, CHANGE media_storage_location_id media_storage_location_id INT DEFAULT NULL, CHANGE recordings_storage_location_id recordings_storage_location_id INT DEFAULT NULL, CHANGE podcasts_storage_location_id podcasts_storage_location_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1C896ABC5 FOREIGN KEY (media_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B15C7361BE FOREIGN KEY (recordings_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B123303CD0 FOREIGN KEY (podcasts_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE station_streamers CHANGE display_name display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_remotes CHANGE display_name display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE station_mounts CHANGE display_name display_name VARCHAR(255) DEFAULT NULL');
    }
}
