<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\ParameterType;
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
        $this->addSql('CREATE TABLE storage_location (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, adapter VARCHAR(50) NOT NULL, path VARCHAR(255) DEFAULT NULL, s3_credential_key VARCHAR(255) DEFAULT NULL, s3_credential_secret VARCHAR(255) DEFAULT NULL, s3_region VARCHAR(150) DEFAULT NULL, s3_version VARCHAR(150) DEFAULT NULL, s3_bucket VARCHAR(255) DEFAULT NULL, s3_endpoint VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE station ADD media_storage_location_id INT DEFAULT NULL, ADD recordings_storage_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1C896ABC5 FOREIGN KEY (media_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B15C7361BE FOREIGN KEY (recordings_storage_location_id) REFERENCES storage_location (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9F39F8B1C896ABC5 ON station (media_storage_location_id)');
        $this->addSql('CREATE INDEX IDX_9F39F8B15C7361BE ON station (recordings_storage_location_id)');

        $this->addSql('ALTER TABLE station_media DROP FOREIGN KEY FK_32AADE3A21BDB235');
        $this->addSql('DROP INDEX IDX_32AADE3A21BDB235 ON station_media');
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
        $this->addSql('ALTER TABLE station_media ADD storage_location_id INT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
// Deleting duplicate streamers to avoid constraint errors in subsequent update
        $stations = $this->connection->fetchAll('SELECT id, radio_base_dir, radio_media_dir FROM station ORDER BY id ASC');

        $directories = [];

        foreach ($stations as $row) {
            $stationId = $row['id'];

            $baseDir = $row['radio_base_dir'];
            $mediaDir = $row['radio_media_dir'];
            if (empty($mediaDir)) {
                $mediaDir = $baseDir . '/media';
            }

            if (isset($directories[$mediaDir])) {
                $directories[$mediaDir]['stations'][] = $stationId;
            } else {
                $directories[$mediaDir] = [
                    'stations' => [$stationId],
                    'albumArtDir' => $baseDir . '/album_art',
                    'waveformsDir' => $baseDir . '/waveforms',
                ];
            }

            // Create recordings dir.
            $this->connection->insert('storage_location', [
                'type' => 'station_recordings',
                'adapter' => 'local',
                'path' => $baseDir . '/recordings',
            ]);

            $recordingsStorageLocationId = $this->connection->lastInsertId('storage_location');

            $this->connection->update('station', [
                'recordings_storage_location_id' => $recordingsStorageLocationId,
            ], [
                'id' => $stationId,
            ]);
        }

        foreach ($directories as $path => $dirInfo) {
            $newAlbumArtDir = $path . '/.albumart';
            rename($dirInfo['albumArtDir'], $newAlbumArtDir);

            $newWaveformsDir = $path . '/.waveforms';
            rename($dirInfo['waveformsDir'], $newWaveformsDir);

            $this->connection->insert('storage_location', [
                'type' => 'station_media',
                'adapter' => 'local',
                'path' => $path,
            ]);

            $mediaStorageLocationId = $this->connection->lastInsertId('storage_location');

            foreach ($dirInfo['stations'] as $stationId) {
                $this->connection->update('station', [
                    'media_storage_location_id' => $mediaStorageLocationId,
                ], [
                    'id' => $stationId,
                ]);
            }

            $firstStationId = array_shift($dirInfo['stations']);

            $this->connection->executeQuery('UPDATE station_media SET storage_location_id=? WHERE station_id = ?',
                [
                    $mediaStorageLocationId,
                    $firstStationId,
                ], [
                    ParameterType::INTEGER,
                    ParameterType::INTEGER,
                ]);

            foreach ($dirInfo['stations'] as $stationId) {
                $media = $this->connection->fetchAllAssociative('SELECT sm1.id AS old_id, sm2.id AS new_id FROM station_media AS sm1 INNER JOIN station_media AS sm2 ON sm1.path = sm2.path WHERE sm2.storage_location_id = ? AND sm1.station_id = ?', [
                    $mediaStorageLocationId,
                    $stationId,
                ], [
                    ParameterType::INTEGER,
                    ParameterType::INTEGER,
                ]);

                $tablesToUpdate = ['song_history', 'station_playlist_media', 'station_queue', 'station_requests'];

                foreach($media as [$oldMediaId, $newMediaId]) {
                    foreach($tablesToUpdate as $table) {
                        $this->connection->update($table, [
                            'media_id' => $newMediaId
                        ], [
                            'media_id' => $oldMediaId
                        ]);
                    }
                }

                $this->connection->executeQuery('DELETE FROM station_media WHRE station_id = ?', [
                    $stationId,
                ], [
                    ParameterType::INTEGER
                ]);
            }
        }
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
