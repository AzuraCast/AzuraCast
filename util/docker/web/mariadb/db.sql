-- MySQL dump 10.17  Distrib 10.3.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mariadb    Database: azuracast
-- ------------------------------------------------------
-- Server version	10.4.13-MariaDB-1:10.4.13+maria~bionic-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES UTF8MB4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `azuracast`;
USE `azuracast`;

ALTER DATABASE `azuracast` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Table structure for table `analytics`
--

DROP TABLE IF EXISTS `analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `number_min` int(11) NOT NULL,
  `number_max` int(11) NOT NULL,
  `number_avg` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EAC2E68821BDB235` (`station_id`),
  KEY `search_idx` (`type`,`timestamp`),
  CONSTRAINT `FK_EAC2E68821BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `id` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verifier` varchar(128) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9579321FA76ED395` (`user_id`),
  CONSTRAINT `FK_9579321FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `app_migrations`
--

DROP TABLE IF EXISTS `app_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_migrations` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_migrations`
--

LOCK TABLES `app_migrations` WRITE;
/*!40000 ALTER TABLE `app_migrations` DISABLE KEYS */;
INSERT INTO `app_migrations` VALUES ('App\\Entity\\Migration\\Version20161003041904',NULL,NULL),('App\\Entity\\Migration\\Version20161006030903',NULL,NULL),('App\\Entity\\Migration\\Version20161007021719',NULL,NULL),('App\\Entity\\Migration\\Version20161007195027',NULL,NULL),('App\\Entity\\Migration\\Version20161117000718',NULL,NULL),('App\\Entity\\Migration\\Version20161117161959',NULL,NULL),('App\\Entity\\Migration\\Version20161120032434',NULL,NULL),('App\\Entity\\Migration\\Version20161122035237',NULL,NULL),('App\\Entity\\Migration\\Version20170412210654',NULL,NULL),('App\\Entity\\Migration\\Version20170414205418',NULL,NULL),('App\\Entity\\Migration\\Version20170423202805',NULL,NULL),('App\\Entity\\Migration\\Version20170424042111',NULL,NULL),('App\\Entity\\Migration\\Version20170502202418',NULL,NULL),('App\\Entity\\Migration\\Version20170510082607',NULL,NULL),('App\\Entity\\Migration\\Version20170510085226',NULL,NULL),('App\\Entity\\Migration\\Version20170510091820',NULL,NULL),('App\\Entity\\Migration\\Version20170512023527',NULL,NULL),('App\\Entity\\Migration\\Version20170512082741',NULL,NULL),('App\\Entity\\Migration\\Version20170512094523',NULL,NULL),('App\\Entity\\Migration\\Version20170516073708',NULL,NULL),('App\\Entity\\Migration\\Version20170516205418',NULL,NULL),('App\\Entity\\Migration\\Version20170516214120',NULL,NULL),('App\\Entity\\Migration\\Version20170516215536',NULL,NULL),('App\\Entity\\Migration\\Version20170518100549',NULL,NULL),('App\\Entity\\Migration\\Version20170522052114',NULL,NULL),('App\\Entity\\Migration\\Version20170524090814',NULL,NULL),('App\\Entity\\Migration\\Version20170606173152',NULL,NULL),('App\\Entity\\Migration\\Version20170618013019',NULL,NULL),('App\\Entity\\Migration\\Version20170619044014',NULL,NULL),('App\\Entity\\Migration\\Version20170619171323',NULL,NULL),('App\\Entity\\Migration\\Version20170622223025',NULL,NULL),('App\\Entity\\Migration\\Version20170719045113',NULL,NULL),('App\\Entity\\Migration\\Version20170803050109',NULL,NULL),('App\\Entity\\Migration\\Version20170823204230',NULL,NULL),('App\\Entity\\Migration\\Version20170829030442',NULL,NULL),('App\\Entity\\Migration\\Version20170906080352',NULL,NULL),('App\\Entity\\Migration\\Version20170917175534',NULL,NULL),('App\\Entity\\Migration\\Version20171022005913',NULL,NULL),('App\\Entity\\Migration\\Version20171103075821',NULL,NULL),('App\\Entity\\Migration\\Version20171104014701',NULL,NULL),('App\\Entity\\Migration\\Version20171124184831',NULL,NULL),('App\\Entity\\Migration\\Version20171128121012',NULL,NULL),('App\\Entity\\Migration\\Version20171208093239',NULL,NULL),('App\\Entity\\Migration\\Version20171214104226',NULL,NULL),('App\\Entity\\Migration\\Version20180203201032',NULL,NULL),('App\\Entity\\Migration\\Version20180203203751',NULL,NULL),('App\\Entity\\Migration\\Version20180203214656',NULL,NULL),('App\\Entity\\Migration\\Version20180204210633',NULL,NULL),('App\\Entity\\Migration\\Version20180206105454',NULL,NULL),('App\\Entity\\Migration\\Version20180211192448',NULL,NULL),('App\\Entity\\Migration\\Version20180320052444',NULL,NULL),('App\\Entity\\Migration\\Version20180320061801',NULL,NULL),('App\\Entity\\Migration\\Version20180320070100',NULL,NULL),('App\\Entity\\Migration\\Version20180320163622',NULL,NULL),('App\\Entity\\Migration\\Version20180320171318',NULL,NULL),('App\\Entity\\Migration\\Version20180324053351',NULL,NULL),('App\\Entity\\Migration\\Version20180412055024',NULL,NULL),('App\\Entity\\Migration\\Version20180415235105',NULL,NULL),('App\\Entity\\Migration\\Version20180417041534',NULL,NULL),('App\\Entity\\Migration\\Version20180425025237',NULL,NULL),('App\\Entity\\Migration\\Version20180425050351',NULL,NULL),('App\\Entity\\Migration\\Version20180428062526',NULL,NULL),('App\\Entity\\Migration\\Version20180429013130',NULL,NULL),('App\\Entity\\Migration\\Version20180506022642',NULL,NULL),('App\\Entity\\Migration\\Version20180608130900',NULL,NULL),('App\\Entity\\Migration\\Version20180716185805',NULL,NULL),('App\\Entity\\Migration\\Version20180818223558',NULL,NULL),('App\\Entity\\Migration\\Version20180826011103',NULL,NULL),('App\\Entity\\Migration\\Version20180826043500',NULL,NULL),('App\\Entity\\Migration\\Version20180830003036',NULL,NULL),('App\\Entity\\Migration\\Version20180909035413',NULL,NULL),('App\\Entity\\Migration\\Version20180909060758',NULL,NULL),('App\\Entity\\Migration\\Version20180909174026',NULL,NULL),('App\\Entity\\Migration\\Version20181016144143',NULL,NULL),('App\\Entity\\Migration\\Version20181025232600',NULL,NULL),('App\\Entity\\Migration\\Version20181120100629',NULL,NULL),('App\\Entity\\Migration\\Version20181126073334',NULL,NULL),('App\\Entity\\Migration\\Version20181202180617',NULL,NULL),('App\\Entity\\Migration\\Version20181211220707',NULL,NULL),('App\\Entity\\Migration\\Version20190124132556',NULL,NULL),('App\\Entity\\Migration\\Version20190128035353',NULL,NULL),('App\\Entity\\Migration\\Version20190314074747',NULL,NULL),('App\\Entity\\Migration\\Version20190314203550',NULL,NULL),('App\\Entity\\Migration\\Version20190315002523',NULL,NULL),('App\\Entity\\Migration\\Version20190324040155',NULL,NULL),('App\\Entity\\Migration\\Version20190326051220',NULL,NULL),('App\\Entity\\Migration\\Version20190331215627','2020-06-09 02:19:27',NULL),('App\\Entity\\Migration\\Version20190402224811','2020-06-09 02:19:27',NULL),('App\\Entity\\Migration\\Version20190429025906','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190429040410','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190513163051','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190517122806','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190624135222','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190715231530','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190719220017','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190810234058','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190813210707','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190818003805','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20190930201744','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20191024185005','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20191101065730','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20191101075303','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200105190343','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200123004338','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200124183957','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200127071620','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200129010322','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200130094654','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200213052842','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200216121137','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200217114139','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200310204315','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200321174535','2020-06-09 02:19:28',NULL),('App\\Entity\\Migration\\Version20200402212036','2020-06-09 02:19:29',NULL),('App\\Entity\\Migration\\Version20200417082209','2020-06-09 02:19:29',NULL),('App\\Entity\\Migration\\Version20200503005148','2020-06-09 02:19:29',NULL),('App\\Entity\\Migration\\Version20200514061004','2020-06-09 02:19:29',NULL),('App\\Entity\\Migration\\Version20200604073027','2020-06-09 02:19:29',NULL),('App\\Entity\\Migration\\Version20200604075356','2020-07-04 17:05:25',137);
/*!40000 ALTER TABLE `app_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
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
  `changes` longtext NOT NULL COMMENT '(DC2Type:array)',
  `user` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field`
--

DROP TABLE IF EXISTS `custom_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `auto_assign` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `listener`
--

DROP TABLE IF EXISTS `listener`;
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
  PRIMARY KEY (`id`),
  KEY `IDX_959C342221BDB235` (`station_id`),
  KEY `idx_timestamps` (`timestamp_end`,`timestamp_start`),
  CONSTRAINT `FK_959C342221BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relays`
--

DROP TABLE IF EXISTS `relays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `base_url` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_visible_on_public_pages` tinyint(1) NOT NULL,
  `nowplaying` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sftp_user`
--

DROP TABLE IF EXISTS `sftp_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sftp_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `public_keys` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_idx` (`username`),
  KEY `IDX_3C32EA3421BDB235` (`station_id`),
  CONSTRAINT `FK_3C32EA3421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `song_history`
--

DROP TABLE IF EXISTS `song_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `song_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` varchar(50) NOT NULL,
  `station_id` int(11) NOT NULL,
  `timestamp_start` int(11) NOT NULL,
  `listeners_start` int(11) DEFAULT NULL,
  `timestamp_end` int(11) NOT NULL,
  `listeners_end` smallint(6) DEFAULT NULL,
  `delta_total` smallint(6) NOT NULL,
  `delta_positive` smallint(6) NOT NULL,
  `delta_negative` smallint(6) NOT NULL,
  `delta_points` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `playlist_id` int(11) DEFAULT NULL,
  `timestamp_cued` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `unique_listeners` smallint(6) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `sent_to_autodj` tinyint(1) NOT NULL,
  `autodj_custom_uri` varchar(255) DEFAULT NULL,
  `streamer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2AD16164A0BDB2F3` (`song_id`),
  KEY `IDX_2AD1616421BDB235` (`station_id`),
  KEY `IDX_2AD161646BBD148` (`playlist_id`),
  KEY `IDX_2AD16164EA9FDD75` (`media_id`),
  KEY `IDX_2AD1616425F432AD` (`streamer_id`),
  KEY `IDX_2AD16164427EB8A5` (`request_id`),
  KEY `idx_timestamp_cued` (`timestamp_cued`),
  KEY `idx_timestamp_start` (`timestamp_start`),
  KEY `idx_timestamp_end` (`timestamp_end`),
  CONSTRAINT `FK_2AD1616421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD1616425F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD16164427EB8A5` FOREIGN KEY (`request_id`) REFERENCES `station_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD161646BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD16164A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2AD16164EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `songs`
--

DROP TABLE IF EXISTS `songs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `songs` (
  `id` varchar(50) NOT NULL,
  `text` varchar(150) DEFAULT NULL,
  `artist` varchar(150) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `created` int(11) NOT NULL,
  `play_count` int(11) NOT NULL,
  `last_played` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `search_idx` (`text`,`artist`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station`
--

DROP TABLE IF EXISTS `station`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `frontend_type` varchar(100) DEFAULT NULL,
  `frontend_config` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `backend_type` varchar(100) DEFAULT NULL,
  `backend_config` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `description` longtext DEFAULT NULL,
  `automation_settings` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `automation_timestamp` int(11) DEFAULT NULL,
  `enable_requests` tinyint(1) NOT NULL,
  `request_delay` int(11) DEFAULT NULL,
  `enable_streamers` tinyint(1) NOT NULL,
  `needs_restart` tinyint(1) NOT NULL,
  `request_threshold` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `radio_media_dir` varchar(255) DEFAULT NULL,
  `radio_base_dir` varchar(255) DEFAULT NULL,
  `has_started` tinyint(1) NOT NULL,
  `nowplaying` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `adapter_api_key` varchar(150) DEFAULT NULL,
  `nowplaying_timestamp` int(11) DEFAULT NULL,
  `enable_public_page` tinyint(1) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `current_streamer_id` int(11) DEFAULT NULL,
  `is_streamer_live` tinyint(1) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `api_history_items` smallint(6) NOT NULL,
  `disconnect_deactivate_streamer` int(11) DEFAULT 0,
  `genre` varchar(150) DEFAULT NULL,
  `storage_quota` bigint(20) DEFAULT NULL,
  `storage_used` bigint(20) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `default_album_art_url` varchar(255) DEFAULT NULL,
  `enable_on_demand` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9F39F8B19B209974` (`current_streamer_id`),
  KEY `idx_short_name` (`short_name`),
  CONSTRAINT `FK_9F39F8B19B209974` FOREIGN KEY (`current_streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_media`
--

DROP TABLE IF EXISTS `station_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `song_id` varchar(50) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `artist` varchar(200) DEFAULT NULL,
  `album` varchar(200) DEFAULT NULL,
  `length` int(11) NOT NULL,
  `length_text` varchar(10) DEFAULT NULL,
  `path` varchar(500) DEFAULT NULL,
  `mtime` int(11) DEFAULT NULL,
  `amplify` decimal(3,1) DEFAULT NULL,
  `fade_overlap` decimal(3,1) DEFAULT NULL,
  `fade_in` decimal(3,1) DEFAULT NULL,
  `fade_out` decimal(3,1) DEFAULT NULL,
  `cue_in` decimal(5,1) DEFAULT NULL,
  `cue_out` decimal(5,1) DEFAULT NULL,
  `isrc` varchar(15) DEFAULT NULL,
  `lyrics` longtext DEFAULT NULL,
  `unique_id` varchar(25) DEFAULT NULL,
  `art_updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path_unique_idx` (`path`,`station_id`),
  KEY `IDX_32AADE3A21BDB235` (`station_id`),
  KEY `IDX_32AADE3AA0BDB2F3` (`song_id`),
  KEY `search_idx` (`title`,`artist`,`album`),
  CONSTRAINT `FK_32AADE3A21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_32AADE3AA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_media_custom_field`
--

DROP TABLE IF EXISTS `station_media_custom_field`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_mounts`
--

DROP TABLE IF EXISTS `station_mounts`;
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
  PRIMARY KEY (`id`),
  KEY `IDX_4DDF64AD21BDB235` (`station_id`),
  CONSTRAINT `FK_4DDF64AD21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlist_folders`
--

DROP TABLE IF EXISTS `station_playlist_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_playlist_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `playlist_id` int(11) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_15190AE921BDB235` (`station_id`),
  KEY `IDX_15190AE96BBD148` (`playlist_id`),
  CONSTRAINT `FK_15190AE921BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_15190AE96BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlist_media`
--

DROP TABLE IF EXISTS `station_playlist_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_playlist_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `playlist_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `last_played` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EA70D7796BBD148` (`playlist_id`),
  KEY `IDX_EA70D779EA9FDD75` (`media_id`),
  CONSTRAINT `FK_EA70D7796BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EA70D779EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `station_media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_playlists`
--

DROP TABLE IF EXISTS `station_playlists`;
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
  `include_in_automation` tinyint(1) NOT NULL,
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
  `queue` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `include_in_on_demand` tinyint(1) NOT NULL,
  `avoid_duplicates` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DC827F7421BDB235` (`station_id`),
  CONSTRAINT `FK_DC827F7421BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_remotes`
--

DROP TABLE IF EXISTS `station_remotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_remotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enable_autodj` tinyint(1) NOT NULL,
  `autodj_format` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autodj_bitrate` smallint(6) DEFAULT NULL,
  `custom_listen_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mount` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_port` smallint(5) unsigned DEFAULT NULL,
  `source_mount` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_visible_on_public_pages` tinyint(1) NOT NULL,
  `relay_id` int(11) DEFAULT NULL,
  `listeners_unique` int(11) NOT NULL,
  `listeners_total` int(11) NOT NULL,
  `admin_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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

DROP TABLE IF EXISTS `station_requests`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_schedules`
--

DROP TABLE IF EXISTS `station_schedules`;
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
  PRIMARY KEY (`id`),
  KEY `IDX_B3BFB2956BBD148` (`playlist_id`),
  KEY `IDX_B3BFB29525F432AD` (`streamer_id`),
  CONSTRAINT `FK_B3BFB29525F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B3BFB2956BBD148` FOREIGN KEY (`playlist_id`) REFERENCES `station_playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_streamer_broadcasts`
--

DROP TABLE IF EXISTS `station_streamer_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_streamer_broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) DEFAULT NULL,
  `streamer_id` int(11) DEFAULT NULL,
  `timestamp_start` int(11) NOT NULL,
  `timestamp_end` int(11) NOT NULL,
  `recording_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_76169D6621BDB235` (`station_id`),
  KEY `IDX_76169D6625F432AD` (`streamer_id`),
  CONSTRAINT `FK_76169D6621BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_76169D6625F432AD` FOREIGN KEY (`streamer_id`) REFERENCES `station_streamers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_streamers`
--

DROP TABLE IF EXISTS `station_streamers`;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique_idx` (`station_id`,`streamer_username`),
  KEY `IDX_5170063E21BDB235` (`station_id`),
  CONSTRAINT `FK_5170063E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `station_webhooks`
--

DROP TABLE IF EXISTS `station_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `station_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL,
  `triggers` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `config` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  `name` varchar(100) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`),
  KEY `IDX_1516958B21BDB235` (`station_id`),
  CONSTRAINT `FK_1516958B21BDB235` FOREIGN KEY (`station_id`) REFERENCES `station` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_has_role`
--

DROP TABLE IF EXISTS `user_has_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_has_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `IDX_EAB8B535A76ED395` (`user_id`),
  KEY `IDX_EAB8B535D60322AC` (`role_id`),
  CONSTRAINT `FK_EAB8B535A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `FK_EAB8B535D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) DEFAULT NULL,
  `auth_password` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `locale` varchar(25) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `theme` varchar(25) DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-07-07 20:12:22
