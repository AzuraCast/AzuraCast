/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.4-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: azuracast
-- ------------------------------------------------------
-- Server version	11.4.4-MariaDB-deb12-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `azuracast`
--

/*!40000 DROP DATABASE IF EXISTS `azuracast`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `azuracast` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `azuracast`;

--
-- Table structure for table `analytics`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `number_min` int(11) NOT NULL,
  `number_max` int(11) NOT NULL,
  `number_avg` decimal(10,2) NOT NULL,
  `moment` datetime NOT NULL,
  `number_unique` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stats_unique_idx` (`station_id`,`type`,`moment`),
  KEY `IDX_EAC2E68821BDB235` (`station_id`),
  KEY `search_idx` (`type`,`moment`),
  CONSTRAINT `FK_EAC2E68821BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_keys`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `id` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verifier` varchar(128) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9579321FA76ED395` (`user_id`),
  CONSTRAINT `FK_9579321FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `app_migrations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_migrations` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `operation` smallint(6) NOT NULL,
  `class` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `target_class` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`changes`)),
  `user` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_search` (`class`,`user`,`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) NOT NULL,
  `auto_assign` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `listener`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `listener` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `listener_uid` int(11) NOT NULL,
  `listener_ip` varchar(45) NOT NULL,
  `listener_user_agent` varchar(255) NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `timestamp_end` int(11) NOT NULL,
  `listener_hash` varchar(32) NOT NULL,
  `mount_id` int(11) DEFAULT NULL,
  `remote_id` int(11) DEFAULT NULL,
  `location_description` varchar(255) NOT NULL,
  `location_region` varchar(150) DEFAULT NULL,
  `location_city` varchar(150) DEFAULT NULL,
  `location_country` varchar(2) DEFAULT NULL,
  `location_lat` decimal(10,6) DEFAULT NULL,
  `location_lon` decimal(10,6) DEFAULT NULL,
  `device_client` varchar(255) NOT NULL,
  `device_is_browser` tinyint(1) NOT NULL,
  `device_is_mobile` tinyint(1) NOT NULL,
  `device_is_bot` tinyint(1) NOT NULL,
  `device_browser_family` varchar(150) DEFAULT NULL,
  `device_os_family` varchar(150) DEFAULT NULL,
  `hls_stream_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_959C342221BDB235` (`station_id`),
  KEY `idx_timestamps` (`timestamp_end`,`timestamp_start`),
  KEY `IDX_959C3422538228B8` (`mount_id`),
  KEY `IDX_959C34222A3E9C94` (`remote_id`),
  KEY `idx_statistics_country` (`location_country`),
  KEY `idx_statistics_os` (`device_os_family`),
  KEY `idx_statistics_browser` (`device_browser_family`),
  KEY `IDX_959C34226FE7D59F` (`hls_stream_id`),
  CONSTRAINT `FK_959C342221BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_959C34222A3E9C94` FOREIGN KEY (`remote_id`) REFERENCES `station_remotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_959C3422538228B8` FOREIGN KEY (`mount_id`) REFERENCES `station_mounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_959C34226FE7D59F` FOREIGN KEY (`hls_stream_id`) REFERENCES `station_hls_streams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `podcast`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `podcast` (
  `id` char(36) NOT NULL,
  `storage_location_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `description` longtext NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `language` varchar(2) NOT NULL,
  `art_updated_at` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `playlist_id` int(11) DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `playlist_auto_publish` tinyint(1) NOT NULL,
  `branding_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`branding_config`)),
  PRIMARY KEY (`id`),
  KEY `IDX_D7E805BDCDDD8AF` (`storage_location_id`),
  KEY `IDX_D7E805BD6BBD148` (`playlist_id`),
  CONSTRAINT `FK_D7E805BD6BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D7E805BDCDDD8AF` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `podcast_category`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `podcast_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podcast_id` char(36) NOT NULL,
  `category` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E633B1E8786136AB` (`podcast_id`),
  CONSTRAINT `FK_E633B1E8786136AB` FOREIGN KEY (`podcast_id`) REFERENCES `podcast` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `podcast_episode`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `podcast_episode` (
  `id` char(36) NOT NULL,
  `podcast_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `description` longtext NOT NULL,
  `publish_at` int(11) NOT NULL,
  `explicit` tinyint(1) NOT NULL,
  `created_at` int(11) NOT NULL,
  `art_updated_at` int(11) NOT NULL,
  `playlist_media_id` int(11) DEFAULT NULL,
  `season_number` int(11) DEFAULT NULL,
  `episode_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_77EB2BD0786136AB` (`podcast_id`),
  KEY `IDX_77EB2BD017421B18` (`playlist_media_id`),
  CONSTRAINT `FK_77EB2BD017421B18` FOREIGN KEY (`playlist_media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_77EB2BD0786136AB` FOREIGN KEY (`podcast_id`) REFERENCES `podcast` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `podcast_media`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `podcast_media` (
  `id` char(36) NOT NULL,
  `storage_location_id` int(11) NOT NULL,
  `episode_id` char(36) DEFAULT NULL,
  `original_name` varchar(200) NOT NULL,
  `length` decimal(7,2) NOT NULL,
  `length_text` varchar(10) NOT NULL,
  `path` varchar(500) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `modified_time` int(11) NOT NULL,
  `art_updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_15AD8829362B62A0` (`episode_id`),
  KEY `IDX_15AD8829CDDD8AF` (`storage_location_id`),
  CONSTRAINT `FK_15AD8829362B62A0` FOREIGN KEY (`episode_id`) REFERENCES `podcast_episode` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_15AD8829CDDD8AF` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relays`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `base_url` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_visible_on_public_pages` tinyint(1) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `action_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_unique_idx` (`role_id`,`action_name`,`station_id`),
  KEY `IDX_1FBA94E6D60322AC` (`role_id`),
  KEY `IDX_1FBA94E621BDB235` (`station_id`),
  CONSTRAINT `FK_1FBA94E621BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1FBA94E6D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `app_unique_identifier` char(36) NOT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `instance_name` varchar(255) DEFAULT NULL,
  `prefer_browser_url` tinyint(1) NOT NULL,
  `use_radio_proxy` tinyint(1) NOT NULL,
  `history_keep_days` smallint(6) NOT NULL,
  `always_use_ssl` tinyint(1) NOT NULL,
  `api_access_control` varchar(255) DEFAULT NULL,
  `enable_static_nowplaying` tinyint(1) NOT NULL,
  `analytics` varchar(50) DEFAULT NULL,
  `check_for_updates` tinyint(1) NOT NULL,
  `update_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`update_results`)),
  `update_last_run` int(11) NOT NULL,
  `public_theme` varchar(50) DEFAULT NULL,
  `hide_album_art` tinyint(1) NOT NULL,
  `homepage_redirect_url` varchar(255) DEFAULT NULL,
  `default_album_art_url` varchar(255) DEFAULT NULL,
  `use_external_album_art_when_processing_media` tinyint(1) NOT NULL,
  `use_external_album_art_in_apis` tinyint(1) NOT NULL,
  `last_fm_api_key` varchar(255) DEFAULT NULL,
  `hide_product_name` tinyint(1) NOT NULL,
  `public_custom_css` longtext DEFAULT NULL,
  `public_custom_js` longtext DEFAULT NULL,
  `internal_custom_css` longtext DEFAULT NULL,
  `backup_enabled` tinyint(1) NOT NULL,
  `backup_time_code` varchar(4) DEFAULT NULL,
  `backup_exclude_media` tinyint(1) NOT NULL,
  `backup_keep_copies` smallint(6) NOT NULL,
  `backup_storage_location` int(11) DEFAULT NULL,
  `backup_last_run` int(11) NOT NULL,
  `backup_last_output` longtext DEFAULT NULL,
  `setup_complete_time` int(11) NOT NULL,
  `external_ip` varchar(45) DEFAULT NULL,
  `geolite_license_key` varchar(255) DEFAULT NULL,
  `geolite_last_run` int(11) NOT NULL,
  `enable_advanced_features` tinyint(1) NOT NULL,
  `mail_enabled` tinyint(1) NOT NULL,
  `mail_sender_name` varchar(255) DEFAULT NULL,
  `mail_sender_email` varchar(255) DEFAULT NULL,
  `mail_smtp_host` varchar(255) DEFAULT NULL,
  `mail_smtp_port` smallint(6) NOT NULL,
  `mail_smtp_username` varchar(255) DEFAULT NULL,
  `mail_smtp_password` varchar(255) DEFAULT NULL,
  `mail_smtp_secure` tinyint(1) NOT NULL,
  `avatar_service` varchar(25) DEFAULT NULL,
  `avatar_default_url` varchar(255) DEFAULT NULL,
  `sync_disabled` tinyint(1) NOT NULL,
  `sync_last_run` int(11) NOT NULL,
  `backup_format` varchar(255) DEFAULT NULL,
  `acme_email` varchar(255) DEFAULT NULL,
  `acme_domains` varchar(255) DEFAULT NULL,
  `ip_source` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`app_unique_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sftp_user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sftp_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `public_keys` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_idx` (`username`),
  KEY `IDX_3C32EA3421BDB235` (`station_id`),
  CONSTRAINT `FK_3C32EA3421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `song_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `song_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `listeners_start` int(11) DEFAULT NULL,
  `timestamp_end` int(11) NOT NULL,
  `listeners_end` int(11) DEFAULT NULL,
  `delta_total` int(11) NOT NULL,
  `delta_positive` int(11) NOT NULL,
  `delta_negative` int(11) NOT NULL,
  `delta_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delta_points`)),
  `playlist_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `unique_listeners` int(11) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `streamer_id` int(11) DEFAULT NULL,
  `text` varchar(512) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2AD1616421BDB235` (`station_id`),
  KEY `IDX_2AD161646BBD148` (`playlist_id`),
  KEY `IDX_2AD16164EA9FDD75` (`media_id`),
  KEY `IDX_2AD1616425F432AD` (`streamer_id`),
  KEY `IDX_2AD16164427EB8A5` (`request_id`),
  KEY `idx_timestamp_start` (`timestamp_start`),
  KEY `idx_timestamp_end` (`timestamp_end`),
  KEY `idx_is_visible` (`is_visible`),
  CONSTRAINT `FK_2AD1616421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD1616425F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2AD16164427EB8A5` FOREIGN KEY (`request_id`) REFERENCES `station_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2AD161646BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_2AD16164EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `frontend_type` varchar(100) NOT NULL,
  `frontend_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`frontend_config`)),
  `backend_type` varchar(100) NOT NULL,
  `backend_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backend_config`)),
  `description` longtext DEFAULT NULL,
  `enable_requests` tinyint(1) NOT NULL,
  `request_delay` int(11) DEFAULT NULL,
  `enable_streamers` tinyint(1) NOT NULL,
  `needs_restart` tinyint(1) NOT NULL,
  `request_threshold` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `radio_base_dir` varchar(255) DEFAULT NULL,
  `has_started` tinyint(1) NOT NULL,
  `adapter_api_key` varchar(150) DEFAULT NULL,
  `enable_public_page` tinyint(1) NOT NULL,
  `short_name` varchar(100) NOT NULL,
  `current_streamer_id` int(11) DEFAULT NULL,
  `is_streamer_live` tinyint(1) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `api_history_items` smallint(6) NOT NULL,
  `disconnect_deactivate_streamer` int(11) DEFAULT 0,
  `genre` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `enable_on_demand` tinyint(1) NOT NULL,
  `media_storage_location_id` int(11) DEFAULT NULL,
  `recordings_storage_location_id` int(11) DEFAULT NULL,
  `enable_on_demand_download` tinyint(1) NOT NULL,
  `podcasts_storage_location_id` int(11) DEFAULT NULL,
  `fallback_path` varchar(255) DEFAULT NULL,
  `enable_hls` tinyint(1) NOT NULL,
  `current_song_id` int(11) DEFAULT NULL,
  `branding_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`branding_config`)),
  `max_bitrate` smallint(6) NOT NULL DEFAULT 0,
  `max_mounts` smallint(6) NOT NULL DEFAULT 0,
  `max_hls_streams` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `IDX_9F39F8B19B209974` (`current_streamer_id`),
  KEY `idx_short_name` (`short_name`),
  KEY `IDX_9F39F8B1C896ABC5` (`media_storage_location_id`),
  KEY `IDX_9F39F8B15C7361BE` (`recordings_storage_location_id`),
  KEY `IDX_9F39F8B123303CD0` (`podcasts_storage_location_id`),
  KEY `IDX_9F39F8B1AB03776` (`current_song_id`),
  CONSTRAINT `FK_9F39F8B123303CD0` FOREIGN KEY (`podcasts_storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9F39F8B15C7361BE` FOREIGN KEY (`recordings_storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9F39F8B19B209974` FOREIGN KEY (`current_streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9F39F8B1AB03776` FOREIGN KEY (`current_song_id`) REFERENCES `song_history` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_9F39F8B1C896ABC5` FOREIGN KEY (`media_storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_hls_streams`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_hls_streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `format` varchar(10) DEFAULT NULL,
  `bitrate` smallint(6) DEFAULT NULL,
  `listeners` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9ECC9CD021BDB235` (`station_id`),
  CONSTRAINT `FK_9ECC9CD021BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_media`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `album` varchar(200) DEFAULT NULL,
  `length` decimal(7,2) NOT NULL,
  `path` varchar(500) NOT NULL,
  `mtime` int(11) NOT NULL,
  `uploaded_at` int(11) NOT NULL,
  `isrc` varchar(15) DEFAULT NULL,
  `lyrics` longtext DEFAULT NULL,
  `unique_id` varchar(25) NOT NULL,
  `art_updated_at` int(11) NOT NULL,
  `text` varchar(512) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `storage_location_id` int(11) NOT NULL,
  `extra_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_metadata`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_unique_idx` (`path`,`storage_location_id`),
  KEY `search_idx` (`title`,`artist`,`album`),
  KEY `IDX_32AADE3ACDDD8AF` (`storage_location_id`),
  CONSTRAINT `FK_32AADE3ACDDD8AF` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_media_custom_field`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_media_custom_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_35DC02AAEA9FDD75` (`media_id`),
  KEY `IDX_35DC02AA443707B0` (`field_id`),
  CONSTRAINT `FK_35DC02AA443707B0` FOREIGN KEY (`field_id`) REFERENCES `custom_field` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_35DC02AAEA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_mounts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_mounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL,
  `fallback_mount` varchar(100) DEFAULT NULL,
  `enable_autodj` tinyint(1) NOT NULL,
  `autodj_format` varchar(10) DEFAULT NULL,
  `autodj_bitrate` smallint(6) DEFAULT NULL,
  `frontend_config` longtext DEFAULT NULL,
  `relay_url` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL,
  `authhash` varchar(255) DEFAULT NULL,
  `custom_listen_url` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `is_visible_on_public_pages` tinyint(1) NOT NULL,
  `listeners_unique` int(11) NOT NULL,
  `listeners_total` int(11) NOT NULL,
  `max_listener_duration` int(11) NOT NULL,
  `intro_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4DDF64AD21BDB235` (`station_id`),
  CONSTRAINT `FK_4DDF64AD21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlist_folders`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_playlist_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `path` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_15190AE921BDB235` (`station_id`),
  KEY `IDX_15190AE96BBD148` (`playlist_id`),
  CONSTRAINT `FK_15190AE921BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_15190AE96BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlist_media`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_playlist_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `playlist_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `last_played` int(11) NOT NULL,
  `is_queued` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EA70D7796BBD148` (`playlist_id`),
  KEY `IDX_EA70D779EA9FDD75` (`media_id`),
  CONSTRAINT `FK_EA70D7796BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EA70D779EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlists`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_playlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `play_per_songs` smallint(6) NOT NULL,
  `play_per_minutes` smallint(6) NOT NULL,
  `weight` smallint(6) NOT NULL,
  `source` varchar(50) NOT NULL,
  `include_in_requests` tinyint(1) NOT NULL,
  `playback_order` varchar(50) NOT NULL,
  `remote_url` varchar(255) DEFAULT NULL,
  `remote_type` varchar(25) DEFAULT NULL,
  `is_jingle` tinyint(1) NOT NULL,
  `play_per_hour_minute` smallint(6) NOT NULL,
  `remote_timeout` smallint(6) NOT NULL,
  `backend_options` varchar(255) DEFAULT NULL,
  `played_at` int(11) NOT NULL,
  `include_in_on_demand` tinyint(1) NOT NULL,
  `avoid_duplicates` tinyint(1) NOT NULL,
  `queue_reset_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DC827F7421BDB235` (`station_id`),
  CONSTRAINT `FK_DC827F7421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_queue`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `playlist_id` int(11) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `sent_to_autodj` tinyint(1) NOT NULL,
  `autodj_custom_uri` varchar(255) DEFAULT NULL,
  `timestamp_cued` int(11) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `text` varchar(512) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_played` tinyint(1) NOT NULL,
  `timestamp_played` int(11) NOT NULL,
  `is_visible` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_277B005521BDB235` (`station_id`),
  KEY `IDX_277B00556BBD148` (`playlist_id`),
  KEY `IDX_277B0055EA9FDD75` (`media_id`),
  KEY `IDX_277B0055427EB8A5` (`request_id`),
  KEY `idx_is_played` (`is_played`),
  KEY `idx_timestamp_played` (`timestamp_played`),
  KEY `idx_sent_to_autodj` (`sent_to_autodj`),
  KEY `idx_timestamp_cued` (`timestamp_cued`),
  CONSTRAINT `FK_277B005521BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_277B0055427EB8A5` FOREIGN KEY (`request_id`) REFERENCES `station_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_277B00556BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_277B0055EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_remotes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_remotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `enable_autodj` tinyint(1) NOT NULL,
  `autodj_format` varchar(10) DEFAULT NULL,
  `autodj_bitrate` smallint(6) DEFAULT NULL,
  `custom_listen_url` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `mount` varchar(150) DEFAULT NULL,
  `source_username` varchar(100) DEFAULT NULL,
  `source_password` varchar(100) DEFAULT NULL,
  `source_port` smallint(5) unsigned DEFAULT NULL,
  `source_mount` varchar(150) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `is_visible_on_public_pages` tinyint(1) NOT NULL,
  `relay_id` int(11) DEFAULT NULL,
  `listeners_unique` int(11) NOT NULL,
  `listeners_total` int(11) NOT NULL,
  `admin_password` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_779D0E8A21BDB235` (`station_id`),
  KEY `IDX_779D0E8A68A482E` (`relay_id`),
  CONSTRAINT `FK_779D0E8A21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_779D0E8A68A482E` FOREIGN KEY (`relay_id`) REFERENCES `relays` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_requests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `played_at` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `skip_delay` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F71F0C0721BDB235` (`station_id`),
  KEY `IDX_F71F0C075ED23C43` (`track_id`),
  CONSTRAINT `FK_F71F0C0721BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_F71F0C075ED23C43` FOREIGN KEY (`track_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_schedules`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `playlist_id` int(11) DEFAULT NULL,
  `streamer_id` int(11) DEFAULT NULL,
  `start_time` smallint(6) NOT NULL,
  `end_time` smallint(6) NOT NULL,
  `start_date` varchar(10) DEFAULT NULL,
  `end_date` varchar(10) DEFAULT NULL,
  `days` varchar(50) DEFAULT NULL,
  `loop_once` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B3BFB2956BBD148` (`playlist_id`),
  KEY `IDX_B3BFB29525F432AD` (`streamer_id`),
  CONSTRAINT `FK_B3BFB29525F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B3BFB2956BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_streamer_broadcasts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_streamer_broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `streamer_id` int(11) NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `timestamp_end` int(11) NOT NULL,
  `recording_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_76169D6621BDB235` (`station_id`),
  KEY `IDX_76169D6625F432AD` (`streamer_id`),
  CONSTRAINT `FK_76169D6621BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_76169D6625F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_streamers`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_streamers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `streamer_username` varchar(50) NOT NULL,
  `streamer_password` varchar(255) NOT NULL,
  `comments` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `reactivate_at` int(11) DEFAULT NULL,
  `enforce_schedule` tinyint(1) NOT NULL,
  `art_updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique_idx` (`station_id`,`streamer_username`),
  KEY `IDX_5170063E21BDB235` (`station_id`),
  CONSTRAINT `FK_5170063E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_webhooks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `triggers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`triggers`)),
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `name` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  KEY `IDX_1516958B21BDB235` (`station_id`),
  CONSTRAINT `FK_1516958B21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `storage_location`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `storage_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `adapter` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `s3_credential_key` varchar(255) DEFAULT NULL,
  `s3_credential_secret` varchar(255) DEFAULT NULL,
  `s3_region` varchar(150) DEFAULT NULL,
  `s3_version` varchar(150) DEFAULT NULL,
  `s3_bucket` varchar(255) DEFAULT NULL,
  `s3_endpoint` varchar(255) DEFAULT NULL,
  `storage_quota` bigint(20) DEFAULT NULL,
  `storage_used` bigint(20) DEFAULT NULL,
  `dropbox_auth_token` varchar(255) DEFAULT NULL,
  `sftp_host` varchar(255) DEFAULT NULL,
  `sftp_username` varchar(255) DEFAULT NULL,
  `sftp_password` varchar(255) DEFAULT NULL,
  `sftp_port` int(11) DEFAULT NULL,
  `sftp_private_key` longtext DEFAULT NULL,
  `sftp_private_key_pass_phrase` varchar(255) DEFAULT NULL,
  `dropbox_app_key` varchar(50) DEFAULT NULL,
  `dropbox_app_secret` varchar(150) DEFAULT NULL,
  `dropbox_refresh_token` varchar(255) DEFAULT NULL,
  `s3_use_path_style` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unprocessable_media`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unprocessable_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storage_location_id` int(11) NOT NULL,
  `path` varchar(500) NOT NULL,
  `mtime` int(11) NOT NULL,
  `error` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_unique_idx` (`path`,`storage_location_id`),
  KEY `IDX_DCB6B9EDCDDD8AF` (`storage_location_id`),
  CONSTRAINT `FK_DCB6B9EDCDDD8AF` FOREIGN KEY (`storage_location_id`) REFERENCES `storage_location` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_has_role`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_has_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `IDX_EAB8B535A76ED395` (`user_id`),
  KEY `IDX_EAB8B535D60322AC` (`role_id`),
  CONSTRAINT `FK_EAB8B535A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EAB8B535D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_login_tokens`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_login_tokens` (
  `id` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `verifier` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DDF24A16A76ED395` (`user_id`),
  CONSTRAINT `FK_DDF24A16A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_passkeys`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_passkeys` (
  `id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `full_id` longtext NOT NULL,
  `public_key_pem` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A2309328A76ED395` (`user_id`),
  CONSTRAINT `FK_A2309328A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `auth_password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `locale` varchar(25) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `show_24_hour_time` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2024-12-09 10:57:50
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.4-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: azuracast
-- ------------------------------------------------------
-- Server version	11.4.4-MariaDB-deb12-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `app_migrations`
--

INSERT INTO `app_migrations` VALUES
('App\\Entity\\Migration\\Version20161003041904','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161006030903','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161007021719','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161007195027','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161117000718','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161117161959','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161120032434','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20161122035237','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170412210654','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170414205418','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170423202805','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170424042111','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170502202418','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170510082607','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170510085226','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170510091820','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170512023527','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170512082741','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170512094523','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170516073708','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170516205418','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170516214120','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170516215536','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170518100549','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170522052114','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170524090814','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170606173152','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170618013019','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170619044014','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170619171323','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170622223025','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170719045113','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170803050109','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170823204230','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170829030442','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170906080352','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20170917175534','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171022005913','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171103075821','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171104014701','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171124184831','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171128121012','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171208093239','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20171214104226','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180203201032','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180203203751','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180203214656','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180204210633','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180206105454','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180211192448','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180320052444','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180320061801','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180320070100','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180320163622','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180320171318','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180324053351','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180412055024','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180415235105','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180417041534','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180425025237','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180425050351','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180428062526','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180429013130','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180506022642','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180608130900','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180716185805','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180818223558','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180826011103','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180826043500','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180830003036','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180909035413','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180909060758','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20180909174026','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181016144143','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181025232600','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181120100629','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181126073334','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181202180617','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20181211220707','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190124132556','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190128035353','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190314074747','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190314203550','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190315002523','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190324040155','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190326051220','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190331215627','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190402224811','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190429025906','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190429040410','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190513163051','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190517122806','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190624135222','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190715231530','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190719220017','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190810234058','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190813210707','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190818003805','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20190930201744','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20191024185005','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20191101065730','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20191101075303','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200105190343','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200123004338','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200124183957','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200127071620','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200129010322','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200130094654','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200213052842','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200216121137','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200217114139','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200310204315','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200321174535','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200402212036','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200417082209','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200503005148','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200514061004','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200604073027','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200604075356','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200711002451','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200816092130','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200818010817','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200825183243','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200826084718','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20200927004829','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201003021913','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201003023117','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201006044905','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201008014439','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201010170333','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201027130404','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201027130504','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201109203951','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201125023226','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201125220258','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201204043539','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201208185538','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201210013351','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201211164613','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201215175111','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201222063647','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20201231011833','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210105061553','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210126191431','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210203030115','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210207015534','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210226053617','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210416214621','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210419033245','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210419043231','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210512225946','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210528180443','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210528211201','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210620131126','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210703185549','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210717164419','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210721011736','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210801020848','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210805004608','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20210828034409','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20211114071609','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20211124165404','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20211227232320','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20211230194621','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220102033308','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220221225704','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220228013328','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220405031647','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220406121125','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220414214828','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220415093355','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220421123900','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220530010809','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220603065416','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220605052847','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220608113502','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220610125828','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220610132810','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220611123923','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220626024436','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220626171758','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220706235608','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20220724223136','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20221008015609','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20221008043751','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20221027134810','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20221102125558','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20221110212745','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230102192033','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230102192652','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230410210554','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230428062001','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230525022221','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230525024711','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230531054836','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230601043650','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230602095822','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230626102616','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230729133644','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230803181406','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230829093303','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20230829124744','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20231125215905','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240221151753','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240319113446','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240319115513','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240421094525','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240425151151','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240511123636','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240519163701','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240529175534','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240619130956','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240619132840','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240702170603','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240706170405','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240710235245','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240813181909','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240817012605','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240818155439','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240822165006','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240901011513','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240902155213','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240911214738','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20240912014811','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20241113155508','2016-01-01 00:00:00',NULL),
('App\\Entity\\Migration\\Version20241123132944','2016-01-01 00:00:00',NULL);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2024-12-09 10:57:50
