<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity()
 */
class Settings
{
    // Predefined settings constants.
    public const BASE_URL = 'base_url';
    public const INSTANCE_NAME = 'instance_name';

    public const PREFER_BROWSER_URL = 'prefer_browser_url';
    public const USE_RADIO_PROXY = 'use_radio_proxy';
    public const HISTORY_KEEP_DAYS = 'history_keep_days';

    public const ALWAYS_USE_SSL = 'always_use_ssl';
    public const API_ACCESS_CONTROL = 'api_access_control';
    public const NOWPLAYING_USE_WEBSOCKETS = 'nowplaying_use_websockets';

    public const LISTENER_ANALYTICS = 'analytics';
    public const CENTRAL_UPDATES = 'central_updates_channel';

    // Custom branding constants.
    public const PUBLIC_THEME = 'public_theme';
    public const HIDE_ALBUM_ART = 'hide_album_art';
    public const HOMEPAGE_REDIRECT_URL = 'homepage_redirect_url';
    public const DEFAULT_ALBUM_ART_URL = 'default_album_art_url';
    public const HIDE_PRODUCT_NAME = 'hide_product_name';
    public const CUSTOM_CSS_PUBLIC = 'custom_css_public';
    public const CUSTOM_JS_PUBLIC = 'custom_js_public';
    public const CUSTOM_CSS_INTERNAL = 'custom_css_internal';

    // Backup settings
    public const BACKUP_ENABLED = 'backup_enabled';
    public const BACKUP_TIME = 'backup_time';
    public const BACKUP_EXCLUDE_MEDIA = 'backup_exclude_media';
    public const BACKUP_KEEP_COPIES = 'backup_keep_copies';
    public const BACKUP_STORAGE_LOCATION = 'backup_storage_location';

    // Internal settings
    public const SETUP_COMPLETE = 'setup_complete';

    public const NOWPLAYING_LAST_STARTED = 'nowplaying_last_started';
    public const NOWPLAYING_LAST_RUN = 'nowplaying_last_run';
    public const NOWPLAYING = 'nowplaying';

    public const SHORT_SYNC_LAST_RUN = 'sync_fast_last_run';
    public const MEDIUM_SYNC_LAST_RUN = 'sync_last_run';
    public const LONG_SYNC_LAST_RUN = 'sync_slow_last_run';

    public const UPDATES_NONE = 0;
    public const UPDATES_ALL = 1;
    public const UPDATES_RELEASE_ONLY = 2;

    public const UNIQUE_IDENTIFIER = 'central_app_uuid';
    public const UPDATE_RESULTS = 'central_update_results';
    public const UPDATE_LAST_RUN = 'central_update_last_run';

    public const EXTERNAL_IP = 'external_ip';

    public const BACKUP_LAST_RUN = 'backup_last_run';
    public const BACKUP_LAST_RESULT = 'backup_last_result';
    public const BACKUP_LAST_OUTPUT = 'backup_last_output';

    public const GEOLITE_LICENSE_KEY = 'geolite_license_key';
    public const GEOLITE_LAST_RUN = 'geolite_last_run';

    /**
     * @ORM\Column(name="setting_key", type="string", length=64)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @var string
     */
    protected $setting_key;

    /**
     * @ORM\Column(name="setting_value", type="json", nullable=true)
     * @var mixed
     */
    protected $setting_value;

    public function __construct(string $setting_key)
    {
        $this->setting_key = $setting_key;
    }

    public function getSettingKey(): string
    {
        return $this->setting_key;
    }

    /**
     * @return mixed
     */
    public function getSettingValue()
    {
        return $this->setting_value;
    }

    public function setSettingValue($setting_value): void
    {
        $this->setting_value = $setting_value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->setting_value;
    }
}
