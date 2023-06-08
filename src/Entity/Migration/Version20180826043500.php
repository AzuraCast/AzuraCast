<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Switch the collation of fields from "utf8mb4_unicode_ci" to "utf8mb4_general_ci" for case-insensitive searching.
 */
final class Version20180826043500 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->changeCharset('utf8mb4_general_ci');
    }

    private function changeCharset(string $collate): void
    {
        $dbName = $this->connection->getDatabase() ?? 'azuracast';

        $tables = [
            'analytics',
            'api_keys',
            'app_migrations',
            'custom_field',
            'listener',
            'role',
            'role_permissions',
            'settings',
            'song_history',
            'songs',
            'station',
            'station_media',
            'station_media_art',
            'station_media_custom_field',
            'station_mounts',
            'station_playlist_media',
            'station_playlists',
            'station_requests',
            'station_requests',
            'station_streamers',
            'station_webhooks',
            'user_has_role',
            'users',
        ];

        $sqlLines = [
            'ALTER DATABASE ' . $this->connection->quoteIdentifier(
                $dbName
            ) . ' CHARACTER SET = utf8mb4 COLLATE = ' . $collate,
            'ALTER TABLE `song_history` DROP FOREIGN KEY FK_2AD16164A0BDB2F3',
            'ALTER TABLE `station_media` DROP FOREIGN KEY FK_32AADE3AA0BDB2F3',
        ];
        foreach ($sqlLines as $sql) {
            $this->addSql($sql);
        }

        foreach ($tables as $tableName) {
            $this->addSql(
                'ALTER TABLE ' . $this->connection->quoteIdentifier(
                    $tableName
                ) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $collate
            );
        }

        $sqlLines = [
            'ALTER TABLE `song_history` ADD CONSTRAINT FK_2AD16164A0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE CASCADE',
            'ALTER TABLE `station_media` ADD CONSTRAINT FK_32AADE3AA0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE SET NULL',
        ];
        foreach ($sqlLines as $sql) {
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->changeCharset('utf8mb4_unicode_ci');
    }
}
