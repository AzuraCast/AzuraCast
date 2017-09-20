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
        if ($this->user !== null && !empty($this->user->getTimezone())) {
            return $this->user->getTimezone();
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
        if ($this->user !== null && !empty($this->user->getLocale()) && $this->user->getLocale() !== 'default') {
            if (isset($supported_locales[$this->user->getLocale()])) {
                $locale = $this->user->getLocale();
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
        if ($this->user !== null && !empty($this->user->getTheme())) {
            $available_themes = $this->app_settings['themes']['available'];
            if (isset($available_themes[$this->user->getTheme()])) {
                return $this->user->getTheme();
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

    /**
     * Get the theme name to be used in public (non-logged-in) pages.
     *
     * @return string
     */
    public function getPublicTheme()
    {
        return $this->settings_repo->getSetting('public_theme', $this->app_settings['themes']['default']);
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicCss()
    {
        return (string)$this->settings_repo->getSetting('custom_css_public', '');
    }

    /**
     * Return the administrator-supplied custom CSS for internal (full layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomInternalCss()
    {
        return (string)$this->settings_repo->getSetting('custom_css_internal', '');
    }
}