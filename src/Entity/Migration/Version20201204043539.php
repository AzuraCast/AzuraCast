<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201204043539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settings migration';
    }

    public function preUp(Schema $schema): void
    {
        $settingsRaw = $this->connection->fetchAllAssociative('SELECT * FROM settings WHERE setting_value IS NOT NULL');

        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = json_decode($row['setting_value'], true, 512, JSON_THROW_ON_ERROR);
        }

        $newSettings = array_filter(
            [
            'baseUrl' => $settings['base_url'] ?? null,
            'instanceName' => $settings['instance_name'] ?? null,
            'preferBrowserUrl' => $this->toBool($settings['prefer_browser_url'] ?? null),
            'useRadioProxy' => $this->toBool($settings['use_radio_proxy'] ?? null),
            'historyKeepDays' => $this->toInt($settings['history_keep_days'] ?? null),
            'alwaysUseSsl' => $this->toBool($settings['always_use_ssl'] ?? null),
            'apiAccessControl' => $settings['api_access_control'] ?? null,
            'enableWebsockets' => $this->toBool($settings['nowplaying_use_websockets'] ?? null),
            'analytics' => $settings['analytics'] ?? null,
            'checkForUpdates' => $this->toBool($settings['central_updates_channel'] ?? null),
            'appUniqueIdentifier' => $settings['central_app_uuid'] ?? null,
            'updateResults' => $settings['central_update_results'] ?? null,
            'updateLastRun' => $this->toInt($settings['central_update_last_run'] ?? null),
            'hideAlbumArt' => $this->toBool($settings['hide_album_art'] ?? null),
            'homepageRedirectUrl' => $settings['homepage_redirect_url'] ?? null,
            'defaultAlbumArtUrl' => $settings['default_album_art_url'] ?? null,
            'hideProductName' => $this->toBool($settings['hide_product_name'] ?? null),
            'publicTheme' => $settings['public_theme'] ?? null,
            'publicCustomCss' => $settings['custom_css_public'] ?? null,
            'publicCustomJs' => $settings['custom_js_public'] ?? null,
            'internalCustomCss' => $settings['custom_css_internal'] ?? null,
            'backupEnabled' => $this->toBool($settings['backup_enabled'] ?? null),
            'backupTimeCode' => $settings['backup_time'] ?? null,
            'backupExcludeMedia' => $this->toBool($settings['backup_exclude_media'] ?? null),
            'backupKeepCopies' => $settings['backup_keep_copies'] ?? null,
            'backupStorageLocation' => $this->toInt($settings['backup_storage_location'] ?? null),
            'backupLastRun' => $this->toInt($settings['backup_last_run'] ?? null),
            'backupLastResult' => $settings['backup_last_result'] ?? null,
                                        'backupLastOutput' => $settings['backup_last_output'] ?? null,
                                        'setupCompleteTime' => $this->toInt($settings['setup_complete'] ?? null),
                                        'nowplaying' => $settings['nowplaying'] ?? null,
                                        'syncNowplayingLastRun' => $this->toInt(
                                            $settings['nowplaying_last_run'] ?? null
                                        ),
                                        'syncShortLastRun' => $this->toInt($settings['sync_fast_last_run'] ?? null),
                                        'syncMediumLastRun' => $this->toInt($settings['sync_last_run'] ?? null),
                                        'syncLongLastRun' => $this->toInt($settings['sync_slow_last_run'] ?? null),
                                        'externalIp' => $settings['external_ip'] ?? null,
                                        'geoliteLicenseKey' => $settings['geolite_license_key'] ?? null,
                                        'geoliteLastRun' => $this->toInt($settings['geolite_last_run'] ?? null),
                                    ],
            static function ($value) {
                return null !== $value;
            }
        );

        $this->connection->delete('settings', [1 => 1]);

        foreach ($newSettings as $settingKey => $settingValue) {
            $this->connection->insert('settings', [
                'setting_key' => $settingKey,
                'setting_value' => json_encode($settingValue, JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function toInt(mixed $value): ?int
    {
        return (null === $value) ? null : (int)$value;
    }

    private function toBool(mixed $value): ?bool
    {
        return (null === $value) ? null : (bool)$value;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('-- "No Query"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('-- "No Query"');
    }
}
