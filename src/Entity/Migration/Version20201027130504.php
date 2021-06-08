<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027130504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Song storage consolidation, part 2.';
    }

    public function preUp(Schema $schema): void
    {
        // Create temp index
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS IDX_TEMP_CONVERT ON station_media (path, storage_location_id, station_id)'
        );

        // Create initial backup directory.
        $this->connection->insert(
            'storage_location',
            [
                'type' => 'backup',
                'adapter' => 'local',
                'path' => '/var/azuracast/backups',
            ]
        );

        $storageLocationId = $this->connection->lastInsertId('storage_location');
        $this->connection->update(
            'settings',
            [
                'setting_value' => $storageLocationId,
            ],
            [
                'setting_key' => 'backup_storage_location',
            ]
        );

        // Migrate existing directories to new StorageLocation paradigm.
        $stations = $this->connection->fetchAllAssociative(
            'SELECT id, radio_base_dir, radio_media_dir, storage_quota FROM station WHERE media_storage_location_id IS NULL ORDER BY id ASC'
        );

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
                    'storageQuota' => $row['storage_quota'],
                    'albumArtDir' => $baseDir . '/album_art',
                    'waveformsDir' => $baseDir . '/waveforms',
                ];
            }

            // Create recordings dir.
            $this->connection->insert(
                'storage_location',
                [
                    'type' => 'station_recordings',
                    'adapter' => 'local',
                    'path' => $baseDir . '/recordings',
                    'storage_quota' => $row['storage_quota'],
                ]
            );

            $recordingsStorageLocationId = $this->connection->lastInsertId('storage_location');

            $this->connection->update(
                'station',
                [
                    'recordings_storage_location_id' => $recordingsStorageLocationId,
                ],
                [
                    'id' => $stationId,
                ]
            );
        }

        foreach ($directories as $path => $dirInfo) {
            $newAlbumArtDir = $path . '/.albumart';
            rename($dirInfo['albumArtDir'], $newAlbumArtDir);

            $newWaveformsDir = $path . '/.waveforms';
            rename($dirInfo['waveformsDir'], $newWaveformsDir);

            $this->connection->insert(
                'storage_location',
                [
                    'type' => 'station_media',
                    'adapter' => 'local',
                    'path' => $path,
                    'storage_quota' => $dirInfo['storageQuota'],
                ]
            );

            $mediaStorageLocationId = $this->connection->lastInsertId('storage_location');

            foreach ($dirInfo['stations'] as $stationId) {
                $this->connection->update(
                    'station',
                    [
                        'media_storage_location_id' => $mediaStorageLocationId,
                    ],
                    [
                        'id' => $stationId,
                    ]
                );
            }

            $firstStationId = array_shift($dirInfo['stations']);

            $this->connection->executeQuery(
                'UPDATE station_media SET storage_location_id=? WHERE station_id = ?',
                [
                    $mediaStorageLocationId,
                    $firstStationId,
                ],
                [
                    ParameterType::INTEGER,
                    ParameterType::INTEGER,
                ]
            );

            foreach ($dirInfo['stations'] as $stationId) {
                $media = $this->connection->fetchAllAssociative(
                    'SELECT sm.id, sm.path FROM station_media AS sm WHERE sm.station_id = ?',
                    [
                        $stationId,
                    ],
                    [
                        ParameterType::INTEGER,
                    ]
                );

                foreach ($media as [$oldMediaId, $mediaPath]) {
                    $newMediaId = $this->connection->fetchOne(
                        'SELECT sm.id FROM station_media AS sm WHERE sm.path = ? AND sm.storage_location_id = ?',
                        [
                            $mediaPath,
                            $mediaStorageLocationId,
                        ],
                        [
                            ParameterType::STRING,
                            ParameterType::INTEGER,
                        ]
                    );

                    if ($newMediaId) {
                        $tablesToUpdate = [
                            'song_history' => 'media_id',
                            'station_playlist_media' => 'media_id',
                            'station_queue' => 'media_id',
                            'station_requests' => 'track_id',
                        ];

                        foreach ($tablesToUpdate as $table => $fieldName) {
                            $this->connection->update(
                                $table,
                                [
                                    $fieldName => $newMediaId,
                                ],
                                [
                                    $fieldName => $oldMediaId,
                                ]
                            );
                        }
                    }
                }

                $this->connection->executeQuery(
                    'DELETE FROM station_media WHERE station_id = ?',
                    [
                        $stationId,
                    ],
                    [
                        ParameterType::INTEGER,
                    ]
                );
            }
        }

        // Drop temp index
        $this->addSql('DROP INDEX IF EXISTS IDX_TEMP_CONVERT ON station_media');
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station_media ADD CONSTRAINT FK_32AADE3ACDDD8AF FOREIGN KEY (storage_location_id) REFERENCES storage_location (id) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_32AADE3ACDDD8AF ON station_media (storage_location_id)');
        $this->addSql('CREATE UNIQUE INDEX path_unique_idx ON station_media (path, storage_location_id)');

        $this->addSql('ALTER TABLE station DROP radio_media_dir, DROP storage_quota, DROP storage_used');

        $this->addSql('ALTER TABLE station_media DROP station_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE station ADD radio_media_dir VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ADD storage_quota BIGINT DEFAULT NULL, ADD storage_used BIGINT DEFAULT NULL'
        );

        $this->addSql('ALTER TABLE station_media ADD station_id INT NOT NULL');

        $this->addSql('ALTER TABLE station_media DROP FOREIGN KEY FK_32AADE3ACDDD8AF');
        $this->addSql('DROP INDEX IDX_32AADE3ACDDD8AF ON station_media');
        $this->addSql('DROP INDEX path_unique_idx ON station_media');
    }
}
