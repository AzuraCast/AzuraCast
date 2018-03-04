<?php
namespace AzuraCast;

use App\Auth;
use Entity;

class Customization
{
    /** @var array */
    protected $app_settings;

    /** @var Entity\User */
    protected $user = null;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct($app_settings, Entity\Repository\SettingsRepository $settings_repo)
    {
        $this->app_settings = $app_settings;
        $this->settings_repo = $settings_repo;
    }

    /**
     * Initialize timezone and locale settings for the current user.
     */
    public function init()
    {
        if (!APP_IS_COMMAND_LINE || APP_TESTING_MODE) {
            // Set time zone.
            date_default_timezone_set($this->getTimeZone());

            // Localization
            $locale = $this->getLocale();
            putenv("LANG=" . $locale);
            setlocale(LC_ALL, $locale);

            $locale_domain = 'default';
            bindtextdomain($locale_domain, APP_INCLUDE_BASE . '/locale');
            bind_textdomain_codeset($locale_domain, 'UTF-8');
            textdomain($locale_domain);
        }
    }

    /**
     * Set the currently active/logged in user.
     *
     * @param Entity\User $user
     */
    public function setUser(\Entity\User $user = null)
    {
        $this->user = $user;
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
        }

        return date_default_timezone_get();
    }

    /**
     * Format the given UNIX timestamp into a locale-friendly time.
     *
     * @param $timestamp
     * @param bool $use_gmt
     * @param bool $show_timezone_abbr
     * @return string Formatted time for presentation.
     */
    public function formatTime($timestamp = null, $use_gmt = false, $show_timezone_abbr = false)
    {
        $timestamp = $timestamp ?? time();

        $time_formats = $this->app_settings['time_formats'];
        $locale = $this->getLocale();

        $time_format = $time_formats[$locale] ?? $time_formats['default'];

        if ($show_timezone_abbr) {
            $time_format .= ($use_gmt) ? ' UTC' : ' %Z';
        }

        return ($use_gmt) ? gmstrftime($time_format, $timestamp) : strftime($time_format, $timestamp);
    }

    /**
     * Return the user-customized, browser-specified or system default locale.
     *
     * @return string
     */
    public function getLocale()
    {
        static $locale = null;

        if ($locale === null) {
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
     * Return the administrator-supplied custom JS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicJs()
    {
        return (string)$this->settings_repo->getSetting('custom_js_public', '');
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

    /**
     * Return whether to show or hide the AzuraCast name from public-facing pages.
     *
     * @return bool
     */
    public function hideProductName(): bool
    {
        return (bool)$this->settings_repo->getSetting('hide_product_name', false);
    }

    /**
     * Return whether to show or hide album art on public pages.
     *
     * @return bool
     */
    public function hideAlbumArt(): bool
    {
        return (bool)$this->settings_repo->getSetting('hide_album_art', false);
    }
}
