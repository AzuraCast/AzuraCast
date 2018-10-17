<?php
namespace App;

use App\Http\Request;
use Gettext\Translations;
use Gettext\Translator;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

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
     * Initialize timezone and locale settings for the current user, and write them as attributes to the request.
     *
     * @param Request $request
     * @return Request
     */
    public function init(Request $request): Request
    {
        $timezone = $this->getTimeZone();

        if (!APP_IS_COMMAND_LINE || APP_TESTING_MODE) {
            // Set time zone.
            date_default_timezone_set($timezone);

            // Localization
            $locale = $this->getLocale();
        } else {
            $locale = $this->app_settings['locale']['default'];
        }

        $translator = new Translator();

        $locale_base = APP_INCLUDE_ROOT.'/resources/locale/compiled';
        $locale_path = $locale_base.'/'.$locale.'.php';

        if (file_exists($locale_path)) {
            $translator->loadTranslations($locale_path);
        }

        // Register translation superglobal functions
        $translator->register();

        putenv("LANG=" . $locale);
        setlocale(LC_ALL, $locale);

        return $request
            ->withAttribute('locale', $locale)
            ->withAttribute('timezone', $timezone);
    }

    /**
     * Set the currently active/logged in user.
     *
     * @param Entity\User $user
     */
    public function setUser(Entity\User $user = null): void
    {
        $this->user = $user;
    }

    /**
     * Get the user's custom time zone or the system default.
     *
     * @return string
     */
    public function getTimeZone(): string
    {
        if ($this->user !== null && !empty($this->user->getTimezone())) {
            return $this->user->getTimezone();
        }

        return $this->getDefaultTimeZone();
    }

    /**
     * Return either the configured global default timezone or the system's regular default.
     *
     * @return string
     */
    public function getDefaultTimeZone(): string
    {
        $global_tz = $this->settings_repo->getSetting('timezone');

        if (!empty($global_tz)) {
            return $global_tz;
        }

        return date_default_timezone_get();
    }

    /**
     * Format the given UNIX timestamp into a locale-friendly time.
     *
     * @param $timestamp
     * @param bool $use_utc
     * @param bool $show_timezone_abbr
     * @return string Formatted time for presentation.
     */
    public function formatTime($timestamp = null, $use_utc = false, $show_timezone_abbr = false)
    {
        $timestamp = $timestamp ?? time();

        $time_formats = $this->app_settings['time_formats'];
        $locale = $this->getLocale();

        $time_format = $time_formats[$locale] ?? $time_formats['default'];

        if ($show_timezone_abbr) {
            $time_format .= ($use_utc) ? ' \U\T\C' : ' T';
        }

        return ($use_utc) ? gmdate($time_format, $timestamp) : date($time_format, $timestamp);
    }

    /**
     * Format a date/time using PHP's IntlDateFormatter constants.
     *
     * @param $timestamp
     * @param bool $use_utc
     * @param int|null $date_display One of:
     *     IntlDateFormatter::NONE - Do not include
     *     IntlDateFormatter::FULL - (Tuesday, April 12, 1952 AD or 3:30:42pm PST)
     *     IntlDateFormatter::LONG (default) - (January 12, 1952 or 3:30:32pm)
     *     IntlDateFormatter::MEDIUM - (Jan 12, 1952)
     *     IntlDateFormatter::SHORT - (12/13/52 or 3:30pm)
     * @param int|null $time_display One of the above.
     * @return string
     */
    public function formatDateTime($timestamp, $use_utc = false, $date_display = \IntlDateFormatter::LONG, $time_display = \IntlDateFormatter::LONG)
    {
        $timezone = ($use_utc) ? 'UTC' : date_default_timezone_get();
        $locale = str_replace('.UTF-8', '', $this->getLocale());

        $fmt = new \IntlDateFormatter(
            $locale,
            $date_display,
            $time_display,
            $timezone,
            \IntlDateFormatter::GREGORIAN
        );

        return $fmt->format($timestamp);
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
            if (in_array($this->user->getTheme(), $available_themes)) {
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
        $public_theme = $this->settings_repo->getSetting('public_theme', null);

        if ($public_theme && in_array($public_theme, $this->app_settings['themes']['available'], true)) {
            return $public_theme;
        }

        return $this->app_settings['themes']['default'];
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

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @return UriInterface
     */
    public function getDefaultAlbumArtUrl(): UriInterface
    {
        $custom_url = trim($this->settings_repo->getSetting('default_album_art_url'));

        if (!empty($custom_url)) {
            return new Uri($custom_url);
        }

        return new Uri('/static/img/generic_song.jpg');
    }

    /**
     * Return the calculated page title given branding settings and the application environment.
     *
     * @param string|null $title
     * @return string
     */
    public function getPageTitle($title = null): string
    {
        if (!$this->hideProductName()) {
            if ($title) {
                $title .= ' - '.$this->app_settings['name'];
            } else {
                $title = $this->app_settings['name'];
            }
        }

        if (!APP_IN_PRODUCTION) {
            $title = '('.ucfirst(APP_APPLICATION_ENV).') '.$title;
        }

        return $title;
    }
}
