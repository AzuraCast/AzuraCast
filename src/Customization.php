<?php
namespace App;

use App\Service\NChan;
use Azura\Settings;
use App\Entity;
use App\Http\Request;
use Gettext\Translator;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class Customization
{
    public const DEFAULT_TIMEZONE = 'UTC';
    public const DEFAULT_LOCALE = 'en_US.UTF-8';
    public const DEFAULT_THEME = 'light';

    /** @var Settings */
    protected $app_settings;

    /** @var Entity\User|null */
    protected $user;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    public function __construct(Settings $app_settings, Entity\Repository\SettingsRepository $settings_repo)
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
        if (!$this->app_settings->isCli() || $this->app_settings->isTesting()) {
            $locale = $this->getLocale();
        } else {
            $locale = self::DEFAULT_LOCALE;
        }

        $translator = new Translator();

        $locale_base = $this->app_settings[Settings::BASE_DIR].'/resources/locale/compiled';
        $locale_path = $locale_base.'/'.$locale.'.php';

        if (file_exists($locale_path)) {
            $translator->loadTranslations($locale_path);
        }

        // Register translation superglobal functions
        $translator->register();

        self::setGlobalValues($locale);
        return $request->withAttribute('locale', $locale);
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
     * Return the user-customized, browser-specified or system default locale.
     *
     * @return string
     */
    public function getLocale(): ?string
    {
        static $locale = null;

        if (null !== $locale) {
            return $locale;
        }

        $supported_locales = $this->app_settings['locale']['supported'];

        // Prefer user-based profile locale.
        if ($this->user !== null && !empty($this->user->getLocale()) && $this->user->getLocale() !== 'default') {
            if (isset($supported_locales[$this->user->getLocale()])) {
                $locale = $this->user->getLocale();
                return $locale;
            }
        }

        // Attempt to load from browser headers.
        $browser_locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null);

        // Prefer exact match.
        $exact_locale = substr($browser_locale, 0, 5).'.UTF-8';

        if (isset($supported_locales[$exact_locale])) {
            $locale = $exact_locale;
            return $locale;
        }

        // Use approximate match if available.
        foreach ($supported_locales as $lang_code => $lang_name) {
            if (strcmp(substr($browser_locale, 0, 2), substr($lang_code, 0, 2)) == 0) {
                $locale = $lang_code;
                return $locale;
            }
        }

        // Default to system option.
        $locale = self::DEFAULT_LOCALE;

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
            return $this->user->getTheme();
        }

        return self::DEFAULT_THEME;
    }

    /**
     * Get the instance name for this AzuraCast instance.
     *
     * @return string|null
     */
    public function getInstanceName(): ?string
    {
        static $instance_name;

        if ($instance_name === null) {
            $instance_name = $this->settings_repo->getSetting(Entity\Settings::INSTANCE_NAME, '');
        }

        return $instance_name;
    }

    /**
     * Get the theme name to be used in public (non-logged-in) pages.
     *
     * @return string
     */
    public function getPublicTheme(): string
    {
        return $this->settings_repo->getSetting(Entity\Settings::PUBLIC_THEME, self::DEFAULT_THEME);
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicCss()
    {
        return (string)$this->settings_repo->getSetting(Entity\Settings::CUSTOM_CSS_PUBLIC, '');
    }

    /**
     * Return the administrator-supplied custom JS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicJs()
    {
        return (string)$this->settings_repo->getSetting(Entity\Settings::CUSTOM_JS_PUBLIC, '');
    }

    /**
     * Return the administrator-supplied custom CSS for internal (full layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomInternalCss()
    {
        return (string)$this->settings_repo->getSetting(Entity\Settings::CUSTOM_CSS_INTERNAL, '');
    }

    /**
     * Return whether to show or hide the AzuraCast name from public-facing pages.
     *
     * @return bool
     */
    public function hideProductName(): bool
    {
        return (bool)$this->settings_repo->getSetting(Entity\Settings::HIDE_PRODUCT_NAME, false);
    }

    /**
     * Return whether to show or hide album art on public pages.
     *
     * @return bool
     */
    public function hideAlbumArt(): bool
    {
        return (bool)$this->settings_repo->getSetting(Entity\Settings::HIDE_ALBUM_ART, false);
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @return UriInterface
     */
    public function getDefaultAlbumArtUrl(): UriInterface
    {
        $custom_url = trim($this->settings_repo->getSetting(Entity\Settings::DEFAULT_ALBUM_ART_URL));

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
                $title .= ' - '.$this->app_settings[Settings::APP_NAME];
            } else {
                $title = $this->app_settings[Settings::APP_NAME];
            }
        }

        if (!$this->app_settings->isProduction()) {
            $title = '('.ucfirst($this->app_settings[Settings::APP_ENV]).') '.$title;
        }

        return $title;
    }

    /**
     * @return bool
     */
    public function useWebSocketsForNowPlaying(): bool
    {
        if (!NChan::isSupported()) {
            return false;
        }

        return (bool)$this->settings_repo->getSetting(Entity\Settings::NOWPLAYING_USE_WEBSOCKETS, true);
    }

    /**
     * @param null $locale
     */
    public static function setGlobalValues($locale = null): void
    {
        if (empty($locale)) {
            $locale = self::DEFAULT_LOCALE;
        }

        putenv("LANG=" . $locale);
        setlocale(\LC_ALL, $locale);
    }

}
