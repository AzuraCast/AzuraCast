<?php
namespace AzuraCast;

use Entity;

class Customization
{
    /** @var array */
    protected $app_settings;

    /** @var Entity\User|null */
    protected $user;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct($app_settings, $user, $settings_repo)
    {
        $this->app_settings = $app_settings;
        $this->user = $user;
        $this->settings_repo = $settings_repo;
    }

    /**
     * Get the user's custom time zone or the system default.
     *
     * @return string
     */
    public function getTimeZone()
    {
        if ($this->user !== null && !empty($this->user->timezone)) {
            return $this->user->timezone;
        } else {
            return date_default_timezone_get();
        }
    }

    /**
     * Return the user-customized, browser-specified or system default locale.
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = null;
        $supported_locales = $this->app_settings['locale']['supported'];

        // Prefer user-based profile locale.
        if ($this->user !== null && !empty($this->user->locale) && $this->user->locale !== 'default') {
            if (isset($supported_locales[$this->user->locale])) {
                $locale = $this->user->locale;
            }
        }

        // Attempt to load from browser headers.
        if (!$locale) {
            $browser_locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

            foreach ($supported_locales as $lang_code => $lang_name) {
                if (strcmp(substr($browser_locale, 0, 2), substr($lang_code, 0, 2)) == 0) {
                    $locale = $lang_code;
                    break;
                }
            }
        }

        // Default to system option.
        if (!$locale) {
            $locale = $this->app_settings['locale']['default'];
        }

        return $locale;
    }

    /**
     * Returns the user-customized or system default theme.
     *
     * @return string
     */
    public function getTheme()
    {
        if ($this->user !== null && !empty($this->user->theme)) {
            $available_themes = $this->app_settings['themes']['available'];
            if (isset($available_themes[$this->user->theme])) {
                return $this->user->theme;
            }
        }

        return $this->app_settings['themes']['default'];
    }

    /**
     * Get the instance name for this AzuraCast instance.
     *
     * @return string|null
     */
    public function getInstanceName()
    {
        static $instance_name;

        if ($instance_name === null) {
            $instance_name = $this->settings_repo->getSetting('instance_name', '');
        }

        return $instance_name;
    }
}