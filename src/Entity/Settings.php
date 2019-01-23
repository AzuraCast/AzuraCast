<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity(repositoryClass="App\Entity\Repository\SettingsRepository")
 */
class Settings
{
    // Predefined settings constants.
    public const BASE_URL              = 'base_url';
    public const INSTANCE_NAME         = 'instance_name';
    public const TIMEZONE              = 'timezone';

    public const PREFER_BROWSER_URL    = 'prefer_browser_url';
    public const USE_RADIO_PROXY       = 'use_radio_proxy';
    public const HISTORY_KEEP_DAYS     = 'history_keep_days';

    public const ALWAYS_USE_SSL        = 'always_use_ssl';
    public const API_ACCESS_CONTROL    = 'api_access_control';

    public const LISTENER_ANALYTICS    = 'analytics';
    public const CENTRAL_UPDATES       = 'central_updates_channel';

    // Custom branding constants.
    public const PUBLIC_THEME          = 'public_theme';
    public const HIDE_ALBUM_ART        = 'hide_album_art';
    public const HOMEPAGE_REDIRECT_URL = 'homepage_redirect_url';
    public const DEFAULT_ALBUM_ART_URL = 'default_album_art_url';
    public const HIDE_PRODUCT_NAME     = 'hide_product_name';
    public const CUSTOM_CSS_PUBLIC     = 'custom_css_public';
    public const CUSTOM_JS_PUBLIC      = 'custom_js_public';
    public const CUSTOM_CSS_INTERNAL   = 'custom_css_internal';

    // Internal settings
    public const SETUP_COMPLETE        = 'setup_complete';

    public const NOWPLAYING_LAST_STARTED = 'nowplaying_last_started';
    public const NOWPLAYING_LAST_RUN   = 'nowplaying_last_run';
    public const SHORT_SYNC_LAST_RUN   = 'sync_fast_last_run';
    public const MEDIUM_SYNC_LAST_RUN  = 'sync_last_run';
    public const LONG_SYNC_LAST_RUN    = 'sync_slow_last_run';

    public const UPDATES_NONE          = 0;
    public const UPDATES_ALL           = 1;
    public const UPDATES_RELEASE_ONLY  = 2;

    public const UNIQUE_IDENTIFIER     = 'central_app_uuid';
    public const UPDATE_RESULTS        = 'central_update_results';
    public const UPDATE_LAST_RUN       = 'central_update_last_run';

    /**
     * @ORM\Column(name="setting_key", type="string", length=64)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @var string
     */
    protected $setting_key;

    /**
     * @ORM\Column(name="setting_value", type="json_array", nullable=true)
     * @var mixed
     */
    protected $setting_value;

    /**
     * Settings constructor.
     * @param string $setting_key
     */
    public function __construct(string $setting_key)
    {
        $this->setting_key = $setting_key;
    }

    /**
     * @return string
     */
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

    /**
     * @see getSettingValue
     * @return mixed
     */
    public function getValue()
    {
        return $this->setting_value;
    }

    /**
     * @param mixed $setting_value
     */
    public function setSettingValue($setting_value)
    {
        $this->setting_value = $setting_value;
    }
}
