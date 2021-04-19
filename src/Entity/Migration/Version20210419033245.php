<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210419033245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settings entity migration, part 1';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE new_settings (app_unique_identifier CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', base_url VARCHAR(255) DEFAULT NULL, instance_name VARCHAR(255) DEFAULT NULL, prefer_browser_url TINYINT(1) NOT NULL, use_radio_proxy TINYINT(1) NOT NULL, history_keep_days SMALLINT NOT NULL, always_use_ssl TINYINT(1) NOT NULL, api_access_control VARCHAR(255) DEFAULT NULL, enable_websockets TINYINT(1) NOT NULL, analytics VARCHAR(50) DEFAULT NULL, check_for_updates TINYINT(1) NOT NULL, update_results LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', update_last_run INT NOT NULL, public_theme VARCHAR(50) DEFAULT NULL, hide_album_art TINYINT(1) NOT NULL, homepage_redirect_url VARCHAR(255) DEFAULT NULL, default_album_art_url VARCHAR(255) DEFAULT NULL, use_external_album_art_when_processing_media TINYINT(1) NOT NULL, use_external_album_art_in_apis TINYINT(1) NOT NULL, last_fm_api_key VARCHAR(255) DEFAULT NULL, hide_product_name TINYINT(1) NOT NULL, public_custom_css LONGTEXT DEFAULT NULL, public_custom_js LONGTEXT DEFAULT NULL, internal_custom_css LONGTEXT DEFAULT NULL, backup_enabled TINYINT(1) NOT NULL, backup_time_code VARCHAR(4) DEFAULT NULL, backup_exclude_media TINYINT(1) NOT NULL, backup_keep_copies SMALLINT NOT NULL, backup_storage_location INT DEFAULT NULL, backup_last_run INT NOT NULL, backup_last_result LONGTEXT DEFAULT NULL, backup_last_output LONGTEXT DEFAULT NULL, setup_complete_time INT NOT NULL, nowplaying LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', sync_nowplaying_last_run INT NOT NULL, sync_short_last_run INT NOT NULL, sync_medium_last_run INT NOT NULL, sync_long_last_run INT NOT NULL, external_ip VARCHAR(45) DEFAULT NULL, geolite_license_key VARCHAR(255) DEFAULT NULL, geolite_last_run INT NOT NULL, enable_advanced_features TINYINT(1) NOT NULL, mail_enabled TINYINT(1) NOT NULL, mail_sender_name VARCHAR(255) DEFAULT NULL, mail_sender_email VARCHAR(255) DEFAULT NULL, mail_smtp_host VARCHAR(255) DEFAULT NULL, mail_smtp_port SMALLINT NOT NULL, mail_smtp_username VARCHAR(255) DEFAULT NULL, mail_smtp_password VARCHAR(255) DEFAULT NULL, mail_smtp_secure TINYINT(1) NOT NULL, avatar_service VARCHAR(25) DEFAULT NULL, avatar_default_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(app_unique_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('RENAME TABLE settings TO old_settings');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE new_settings');
        $this->addSql('RENAME TABLE old_settings TO settings');
    }
}
