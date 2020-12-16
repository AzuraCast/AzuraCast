<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027130404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Song storage consolidation, part 1.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS storage_location(id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, adapter VARCHAR(50) NOT NULL, path VARCHAR(255) DEFAULT NULL, s3_credential_key VARCHAR(255) DEFAULT NULL, s3_credential_secret VARCHAR(255) DEFAULT NULL, s3_region VARCHAR(150) DEFAULT NULL, s3_version VARCHAR(150) DEFAULT NULL, s3_bucket VARCHAR(255) DEFAULT NULL, s3_endpoint VARCHAR(255) DEFAULT NULL, storage_quota BIGINT DEFAULT NULL, storage_used BIGINT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE station ADD IF NOT EXISTS media_storage_location_id INT DEFAULT NULL, ADD IF NOT EXISTS recordings_storage_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1C896ABC5 FOREIGN KEY IF NOT EXISTS (media_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B15C7361BE FOREIGN KEY IF NOT EXISTS (recordings_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9F39F8B1C896ABC5 ON station (media_storage_location_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9F39F8B15C7361BE ON station (recordings_storage_location_id)');

        $this->addSql('ALTER TABLE station_media DROP FOREIGN KEY FK_32AADE3A21BDB235');
        $this->addSql('DROP INDEX IDX_32AADE3A21BDB235 ON station_media');
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
        $this->addSql('ALTER TABLE station_media ADD IF NOT EXISTS storage_location_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1C896ABC5');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B15C7361BE');
        $this->addSql('DROP INDEX IDX_9F39F8B1C896ABC5 ON station');
        $this->addSql('DROP INDEX IDX_9F39F8B15C7361BE ON station');

        $this->addSql('DROP TABLE storage_location');

        $this->addSql('ALTER TABLE station DROP media_storage_location_id, DROP recordings_storage_location_id');
        $this->addSql('DROP INDEX IDX_32AADE3ACDDD8AF ON station_media');
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
        $this->addSql('ALTER TABLE station_media DROP storage_location_id');
        $this->addSql('ALTER TABLE station_media CHANGE storage_location_id station_id INT NOT NULL');
        $this->addSql('ALTER TABLE station_media ADD CONSTRAINT FK_32AADE3A21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_32AADE3A21BDB235 ON station_media (station_id)');
        $this->addSql('CREATE UNIQUE INDEX path_unique_idx ON station_media (path, station_id)');
    }
}
