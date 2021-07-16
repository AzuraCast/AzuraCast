<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20161003041904 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Apply the original database schema.
        $initialSql = [
            'SET NAMES utf8mb4',
            'SET FOREIGN_KEY_CHECKS=0',

            'CREATE TABLE IF NOT EXISTS `action` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `analytics` (`id` int(11) NOT NULL AUTO_INCREMENT, `station_id` int(11) DEFAULT NULL, `type` varchar(15) COLLATE utf8_unicode_ci NOT NULL, `timestamp` int(11) NOT NULL, `number_min` int(11) NOT NULL, `number_max` int(11) NOT NULL, `number_avg` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `IDX_EAC2E68821BDB235` (`station_id`), KEY `search_idx` (`type`,`timestamp`), CONSTRAINT `FK_EAC2E68821BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `api_keys` (`id` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `owner` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL, `calls_made` int(11) NOT NULL, `created` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `role` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `role_has_action` (`role_id` int(11) NOT NULL, `action_id` int(11) NOT NULL, PRIMARY KEY (`role_id`,`action_id`), KEY `IDX_E4DAF125D60322AC` (`role_id`), KEY `IDX_E4DAF1259D32F035` (`action_id`), CONSTRAINT `FK_E4DAF1259D32F035` FOREIGN KEY (`action_id`) REFERENCES `action` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_E4DAF125D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `settings` (`setting_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL, `setting_value` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', PRIMARY KEY (`setting_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `songs` (`id` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `text` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL, `artist` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL, `title` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL, `created` int(11) NOT NULL, `play_count` int(11) NOT NULL, `last_played` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `search_idx` (`text`,`artist`,`title`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `song_history` (`id` int(11) NOT NULL AUTO_INCREMENT, `song_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `station_id` int(11) NOT NULL, `timestamp_start` int(11) NOT NULL, `listeners_start` int(11) DEFAULT NULL, `timestamp_end` int(11) NOT NULL, `listeners_end` smallint(6) DEFAULT NULL, `delta_total` smallint(6) NOT NULL, `delta_positive` smallint(6) NOT NULL, `delta_negative` smallint(6) NOT NULL, `delta_points` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', PRIMARY KEY (`id`), KEY `IDX_2AD16164A0BDB2F3` (`song_id`), KEY `IDX_2AD1616421BDB235` (`station_id`), KEY `sort_idx` (`timestamp_start`), CONSTRAINT `FK_2AD1616421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_2AD16164A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, `frontend_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, `frontend_config` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', `backend_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, `backend_config` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', `description` longtext COLLATE utf8_unicode_ci, `radio_base_dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, `nowplaying_data` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', `automation_settings` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', `automation_timestamp` int(11) DEFAULT NULL, `enable_requests` tinyint(1) NOT NULL, `request_delay` int(11) DEFAULT NULL, `enable_streamers` tinyint(1) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station_media` ( `id` int(11) NOT NULL AUTO_INCREMENT, `station_id` int(11) NOT NULL, `song_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL, `title` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL, `artist` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL, `album` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL, `length` smallint(6) NOT NULL, `length_text` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL, `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, `mtime` int(11) DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `path_unique_idx` (`path`), KEY `IDX_32AADE3A21BDB235` (`station_id`), KEY `IDX_32AADE3AA0BDB2F3` (`song_id`), KEY `search_idx` (`title`,`artist`,`album`), CONSTRAINT `FK_32AADE3A21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_32AADE3AA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station_playlists` ( `id` int(11) NOT NULL AUTO_INCREMENT, `station_id` int(11) NOT NULL, `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL, `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `is_enabled` tinyint(1) NOT NULL, `play_per_songs` smallint(6) NOT NULL, `play_per_minutes` smallint(6) NOT NULL, `schedule_start_time` smallint(6) NOT NULL, `schedule_end_time` smallint(6) NOT NULL, `play_once_time` smallint(6) NOT NULL, `weight` smallint(6) NOT NULL, `include_in_automation` tinyint(1) NOT NULL, PRIMARY KEY (`id`), KEY `IDX_DC827F7421BDB235` (`station_id`), CONSTRAINT `FK_DC827F7421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station_playlist_has_media` ( `media_id` int(11) NOT NULL, `playlists_id` int(11) NOT NULL, PRIMARY KEY (`media_id`,`playlists_id`), KEY `IDX_668E6486EA9FDD75` (`media_id`), KEY `IDX_668E64869F70CF56` (`playlists_id`), CONSTRAINT `FK_668E64869F70CF56` FOREIGN KEY (`playlists_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_668E6486EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station_requests` ( `id` int(11) NOT NULL AUTO_INCREMENT, `station_id` int(11) NOT NULL, `track_id` int(11) NOT NULL, `timestamp` int(11) NOT NULL, `played_at` int(11) NOT NULL, `ip` varchar(40) COLLATE utf8_unicode_ci NOT NULL, PRIMARY KEY (`id`), KEY `IDX_F71F0C0721BDB235` (`station_id`), KEY `IDX_F71F0C075ED23C43` (`track_id`), CONSTRAINT `FK_F71F0C0721BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_F71F0C075ED23C43` FOREIGN KEY (`track_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `station_streamers` ( `id` int(11) NOT NULL AUTO_INCREMENT, `station_id` int(11) NOT NULL, `streamer_username` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `streamer_password` varchar(50) COLLATE utf8_unicode_ci NOT NULL, `comments` longtext COLLATE utf8_unicode_ci, `is_active` tinyint(1) NOT NULL, PRIMARY KEY (`id`), KEY `IDX_5170063E21BDB235` (`station_id`), CONSTRAINT `FK_5170063E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `users` ( `uid` int(11) NOT NULL AUTO_INCREMENT, `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, `auth_password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, `auth_last_login_time` int(11) DEFAULT NULL, `auth_recovery_code` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL, `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL, `gender` varchar(1) COLLATE utf8_unicode_ci DEFAULT NULL, `customization` longtext COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', PRIMARY KEY (`uid`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `user_has_role` ( `user_id` int(11) NOT NULL, `role_id` int(11) NOT NULL, PRIMARY KEY (`user_id`,`role_id`), KEY `IDX_EAB8B535A76ED395` (`user_id`), KEY `IDX_EAB8B535D60322AC` (`role_id`), CONSTRAINT `FK_EAB8B535A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE, CONSTRAINT `FK_EAB8B535D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `user_manages_station` ( `user_id` int(11) NOT NULL, `station_id` int(11) NOT NULL, PRIMARY KEY (`user_id`,`station_id`), KEY `IDX_2453B56BA76ED395` (`user_id`), KEY `IDX_2453B56B21BDB235` (`station_id`), CONSTRAINT `FK_2453B56B21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE, CONSTRAINT `FK_2453B56BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci',

            'CREATE TABLE role_has_actions (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, role_id INT NOT NULL, action_id INT NOT NULL, INDEX IDX_50EEC1BDD60322AC (role_id), INDEX IDX_50EEC1BD21BDB235 (station_id), UNIQUE INDEX role_action_unique_idx (role_id, action_id, station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BD21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE',
            'INSERT INTO role_has_actions (role_id, action_id) SELECT role_id, action_id FROM role_has_action',
            'DROP TABLE role_has_action',
            'ALTER TABLE action ADD is_global TINYINT(1) NOT NULL',
            'UPDATE action SET is_global=1',

            'SET FOREIGN_KEY_CHECKS=1',
        ];

        foreach ($initialSql as $sqlLine) {
            $this->addSql($sqlLine);
        }

        $actions = [
            ['view administration', 1],
            ['administer settings', 1],
            ['administer api keys', 1],
            ['administer user accounts', 1],
            ['administer permissions', 1],
            ['administer stations', 1],

            ['view station management', 0],
            ['view station reports', 0],
            ['manage station profile', 0],
            ['manage station broadcasting', 0],
            ['manage station streamers', 0],
            ['manage station media', 0],
            ['manage station automation', 0],
        ];

        foreach ($actions as $action) {
            $this->addSql('DELETE FROM action WHERE name = :name', [
                'name' => $action[0],
            ], [
                'string' => 'string',
            ]);

            $this->addSql('INSERT INTO action (name, is_global) VALUES (:name, :is_global)', [
                'name' => $action[0],
                'is_global' => $action[1],
            ], [
                'string' => 'string',
                'int' => 'int',
            ]);
        }
    }
}
