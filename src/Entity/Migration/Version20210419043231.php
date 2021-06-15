<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210419043231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settings entity migration, part 2';
    }

    public function preUp(Schema $schema): void
    {
        $oldSettings = [];

        $oldSettingsRaw = $this->connection->fetchAllAssociative(
            'SELECT setting_key, setting_value FROM old_settings'
        );

        foreach ($oldSettingsRaw as $row) {
            $key = $row['setting_key'];
            $key = mb_strtolower(preg_replace('~(?<=\\w)([A-Z])~u', '_$1', $key));

            $value = $row['setting_value'];
            $value = ($value === null || $value === '')
                ? null
                : json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            $oldSettings[$key] = $value;
        }

        $newSettings = [];

        $appUniqueIdentifier = $oldSettings['app_unique_identifier'] ?? null;
        if (empty($appUniqueIdentifier)) {
            $appUniqueIdentifier = $this->connection->executeQuery('SELECT UUID()')->fetchOne();
        }

        $newSettings['app_unique_identifier'] = $appUniqueIdentifier;

        $textFields = [
            'public_custom_css',
            'public_custom_js',
            'internal_custom_css',
            'backup_last_result',
            'backup_last_output',
        ];

        $stringFields = [
            'base_url' => 255,
            'instance_name' => 255,
            'api_access_control' => 255,
            'analytics' => 50,
            'public_theme' => 50,
            'homepage_redirect_url' => 255,
            'default_album_art_url' => 255,
            'last_fm_api_key' => 255,
            'backup_time_code' => 4,
            'external_ip' => 45,
            'geolite_license_key' => 255,
            'mail_sender_name' => 255,
            'mail_sender_email' => 255,
            'mail_smtp_host' => 255,
            'mail_smtp_username' => 255,
            'mail_smtp_password' => 255,
            'avatar_service' => 25,
            'avatar_default_url' => 255,
        ];

        $boolFields = [
            'prefer_browser_url',
            'use_radio_proxy',
            'always_use_ssl',
            'enable_websockets',
            'check_for_updates',
            'hide_album_art',
            'use_external_album_art_when_processing_media',
            'use_external_album_art_in_apis',
            'hide_product_name',
            'backup_enabled',
            'backup_exclude_media',
            'enable_advanced_features',
            'mail_enabled',
            'mail_smtp_secure',
        ];

        $smallIntFields = [
            'history_keep_days',
            'backup_keep_copies',
            'mail_smtp_port',
        ];

        $intFields = [
            'update_last_run',
            'backup_storage_location',
            'backup_last_run',
            'setup_complete_time',
            'sync_nowplaying_last_run',
            'sync_short_last_run',
            'sync_medium_last_run',
            'sync_long_last_run',
            'geolite_last_run',
        ];

        foreach ($textFields as $field) {
            $value = $oldSettings[$field] ?? null;
            if (null === $value) {
                continue;
            }

            $newSettings[$field] = $value;
        }

        foreach ($stringFields as $field => $length) {
            $value = $oldSettings[$field] ?? null;
            if (null === $value) {
                continue;
            }

            $newSettings[$field] = mb_substr((string)$value, 0, $length, 'UTF-8');
        }

        foreach ($boolFields as $field) {
            $value = $oldSettings[$field] ?? null;
            if (null === $value) {
                $newSettings[$field] = 0;
                continue;
            }

            $newSettings[$field] = $value ? 1 : 0;
        }

        foreach ($smallIntFields as $field) {
            $value = $oldSettings[$field] ?? null;
            if (null === $value) {
                $newSettings[$field] = 0;
                continue;
            }

            $value = (int)$value;
            if ($value > 32767) {
                $value = 32767;
            }

            $newSettings[$field] = $value;
        }

        foreach ($intFields as $field) {
            $value = $oldSettings[$field] ?? null;
            if (null === $value) {
                $value = 0;
            }

            $newSettings[$field] = (int)$value;
        }

        $this->connection->executeQuery('DELETE FROM new_settings');
        $this->connection->insert('new_settings', $newSettings);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE new_settings TO settings');
        $this->addSql('DROP TABLE old_settings');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE settings TO new_settings');
        $this->addSql('CREATE TABLE old_settings (setting_key VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, setting_value LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json)\', PRIMARY KEY(setting_key)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }
}
