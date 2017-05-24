/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE TABLE IF NOT EXISTS `analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `number_min` int(11) NOT NULL,
  `number_max` int(11) NOT NULL,
  `number_avg` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EAC2E68821BDB235` (`station_id`),
  KEY `search_idx` (`type`,`timestamp`),
  CONSTRAINT `FK_EAC2E68821BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `analytics` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `owner` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `calls_made` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `app_migrations` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `app_migrations` DISABLE KEYS */;
INSERT INTO `app_migrations` (`version`) VALUES
  ('20161003041904'),
  ('20161006030903'),
  ('20161007021719'),
  ('20161007195027'),
  ('20161117000718'),
  ('20161117161959'),
  ('20161120032434'),
  ('20161122035237'),
  ('20170412210654'),
  ('20170414205418'),
  ('20170423202805'),
  ('20170424042111'),
  ('20170502202418'),
  ('20170510082607'),
  ('20170510085226'),
  ('20170510091820'),
  ('20170512023527'),
  ('20170512082741'),
  ('20170512094523'),
  ('20170516073708'),
  ('20170516205418'),
  ('20170516214120'),
  ('20170516215536'),
  ('20170518100549'),
  ('20170522052114'),
  ('20170524090814');
/*!40000 ALTER TABLE `app_migrations` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `listener` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `listener_uid` int(11) NOT NULL,
  `listener_ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `listener_user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `timestamp_end` int(11) NOT NULL,
  `listener_hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_959C342221BDB235` (`station_id`),
  KEY `update_idx` (`listener_hash`),
  KEY `search_idx` (`listener_uid`,`timestamp_end`),
  CONSTRAINT `FK_959C342221BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `listener` DISABLE KEYS */;
/*!40000 ALTER TABLE `listener` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `role` DISABLE KEYS */;
/*!40000 ALTER TABLE `role` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `action_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_unique_idx` (`role_id`,`action_name`,`station_id`),
  KEY `IDX_1FBA94E6D60322AC` (`role_id`),
  KEY `IDX_1FBA94E621BDB235` (`station_id`),
  CONSTRAINT `FK_1FBA94E621BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1FBA94E6D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `songs` (
  `id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `text` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `artist` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` int(11) NOT NULL,
  `play_count` int(11) NOT NULL,
  `last_played` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `search_idx` (`text`,`artist`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `songs` DISABLE KEYS */;
/*!40000 ALTER TABLE `songs` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `song_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `station_id` int(11) NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `listeners_start` int(11) DEFAULT NULL,
  `timestamp_end` int(11) NOT NULL,
  `listeners_end` smallint(6) DEFAULT NULL,
  `delta_total` smallint(6) NOT NULL,
  `delta_positive` smallint(6) NOT NULL,
  `delta_negative` smallint(6) NOT NULL,
  `delta_points` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `playlist_id` int(11) DEFAULT NULL,
  `timestamp_cued` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `unique_listeners` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2AD16164427EB8A5` (`request_id`),
  KEY `IDX_2AD16164A0BDB2F3` (`song_id`),
  KEY `IDX_2AD1616421BDB235` (`station_id`),
  KEY `sort_idx` (`timestamp_start`),
  KEY `IDX_2AD161646BBD148` (`playlist_id`),
  CONSTRAINT `FK_2AD1616421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD16164427EB8A5` FOREIGN KEY (`request_id`) REFERENCES `station_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD161646BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD16164A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `song_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `song_history` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frontend_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frontend_config` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `backend_type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `backend_config` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `description` longtext COLLATE utf8_unicode_ci,
  `nowplaying_data` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `automation_settings` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `automation_timestamp` int(11) DEFAULT NULL,
  `enable_requests` tinyint(1) NOT NULL,
  `request_delay` int(11) DEFAULT NULL,
  `enable_streamers` tinyint(1) NOT NULL,
  `needs_restart` tinyint(1) NOT NULL,
  `request_threshold` int(11) DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `radio_media_dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `radio_base_dir` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station` DISABLE KEYS */;
/*!40000 ALTER TABLE `station` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `song_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `artist` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `album` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `length` smallint(6) NOT NULL,
  `length_text` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mtime` int(11) DEFAULT NULL,
  `fade_overlap` decimal(3,1) DEFAULT NULL,
  `fade_in` decimal(3,1) DEFAULT NULL,
  `fade_out` decimal(3,1) DEFAULT NULL,
  `cue_in` decimal(3,1) DEFAULT NULL,
  `cue_out` decimal(3,1) DEFAULT NULL,
  `isrc` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_unique_idx` (`path`,`station_id`),
  KEY `IDX_32AADE3A21BDB235` (`station_id`),
  KEY `IDX_32AADE3AA0BDB2F3` (`song_id`),
  KEY `search_idx` (`title`,`artist`,`album`),
  CONSTRAINT `FK_32AADE3A21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_32AADE3AA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_media` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_mounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL,
  `fallback_mount` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enable_autodj` tinyint(1) NOT NULL,
  `autodj_format` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `autodj_bitrate` smallint(6) DEFAULT NULL,
  `frontend_config` longtext COLLATE utf8_unicode_ci,
  `relay_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4DDF64AD21BDB235` (`station_id`),
  CONSTRAINT `FK_4DDF64AD21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_mounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_mounts` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_playlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `play_per_songs` smallint(6) NOT NULL,
  `play_per_minutes` smallint(6) NOT NULL,
  `schedule_start_time` smallint(6) NOT NULL,
  `schedule_end_time` smallint(6) NOT NULL,
  `play_once_time` smallint(6) NOT NULL,
  `weight` smallint(6) NOT NULL,
  `include_in_automation` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DC827F7421BDB235` (`station_id`),
  CONSTRAINT `FK_DC827F7421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_playlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_playlists` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_playlist_has_media` (
  `media_id` int(11) NOT NULL,
  `playlists_id` int(11) NOT NULL,
  PRIMARY KEY (`media_id`,`playlists_id`),
  KEY `IDX_668E6486EA9FDD75` (`media_id`),
  KEY `IDX_668E64869F70CF56` (`playlists_id`),
  CONSTRAINT `FK_668E64869F70CF56` FOREIGN KEY (`playlists_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_668E6486EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_playlist_has_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_playlist_has_media` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `played_at` int(11) NOT NULL,
  `ip` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F71F0C0721BDB235` (`station_id`),
  KEY `IDX_F71F0C075ED23C43` (`track_id`),
  CONSTRAINT `FK_F71F0C0721BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F71F0C075ED23C43` FOREIGN KEY (`track_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_requests` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `station_streamers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `streamer_username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `streamer_password` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `comments` longtext COLLATE utf8_unicode_ci,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5170063E21BDB235` (`station_id`),
  CONSTRAINT `FK_5170063E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `station_streamers` DISABLE KEYS */;
/*!40000 ALTER TABLE `station_streamers` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locale` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `theme` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `user_has_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `IDX_EAB8B535A76ED395` (`user_id`),
  KEY `IDX_EAB8B535D60322AC` (`role_id`),
  CONSTRAINT `FK_EAB8B535A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `FK_EAB8B535D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40000 ALTER TABLE `user_has_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_has_role` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;