<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170829030442 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->changeCharset('utf8mb4', 'utf8mb4_unicode_ci');
    }

    private function changeCharset(string $charset, string $collate): void
    {
        $dbName = $this->connection->getDatabase() ?? 'azuracast';

        $sqlLines = [
            'ALTER TABLE listener CHANGE listener_user_agent listener_user_agent VARCHAR(255) NOT NULL',
            'ALTER TABLE station CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE radio_base_dir radio_base_dir VARCHAR(255) DEFAULT NULL, CHANGE radio_media_dir radio_media_dir VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE station_media CHANGE path path VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE station_mounts CHANGE relay_url relay_url VARCHAR(255) DEFAULT NULL, CHANGE authhash authhash VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE users CHANGE auth_password auth_password VARCHAR(255) DEFAULT NULL',
            'ALTER TABLE app_migrations CHANGE version version VARCHAR(191) NOT NULL',
            'ALTER DATABASE ' . $this->connection->quoteIdentifier($dbName) . ' CHARACTER SET = ' . $charset . ' COLLATE = ' . $collate,
            'ALTER TABLE `song_history` DROP FOREIGN KEY FK_2AD16164A0BDB2F3',
            'ALTER TABLE `station_media` DROP FOREIGN KEY FK_32AADE3AA0BDB2F3',
            'ALTER TABLE `analytics` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `api_keys` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `app_migrations` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `listener` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `role` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `role_permissions` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `settings` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `song_history` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `songs` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_media` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_mounts` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_playlist_has_media` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_playlists` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_requests` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `station_streamers` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `user_has_role` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
            'ALTER TABLE `users` CONVERT TO CHARACTER SET ' . $charset . ' COLLATE ' . $collate,
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
        $this->changeCharset('utf8', 'utf8_unicode_ci');
    }
}
