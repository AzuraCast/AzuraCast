<?php
namespace App;

use App\Entity;
use App\Service\NChan;
use Gettext\Translator;
use GuzzleHttp\Psr7\Uri;
use Locale;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use const LC_ALL;

class Customization
{
    public const DEFAULT_TIMEZONE = 'UTC';
    public const DEFAULT_LOCALE = 'en_US.UTF-8';
    public const DEFAULT_THEME = 'light';

    /** @var Entity\User|null */
    protected $user;

    /** @var Entity\Repository\SettingsRepository */
    protected $settingsRepo;

    /** @var string|null */
    protected $locale;

    public function __construct(Entity\Repository\SettingsRepository $settingsRepo)
    {
        $this->settingsRepo = $settingsRepo;
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
     * Initialize timezone and locale settings for the current user, and write them as attributes to the request.
     *
     * @param Request|null $request
     *
     * @return Request|null
     */
    public function init(?Request $request = null): ?Request
    {
        $this->locale = $this->initLocale($request);

        // Set up the PHP translator
        $translator = new Translator();

        $locale_base = Settings::getInstance()->getBaseDirectory() . '/resources/locale/compiled';
        $locale_path = $locale_base . '/' . $this->locale . '.php';

        if (file_exists($locale_path)) {
            $translator->loadTranslations($locale_path);
        }

        $translator->register();

        // Register translation superglobal functions
        putenv('LANG=' . $this->locale);
        setlocale(LC_ALL, $this->locale);

        if ($request instanceof Request) {
            $request = $request->withAttribute('locale', $this->locale);
        }

        return $request;
    }

    /**
     * Return the user-customized, browser-specified or system default locale.
     *
     * @param Request|null $request
     *
     * @return string|null
     */
    protected function initLocale(?Request $request = null): ?string
    {
        $settings = Settings::getInstance();
        if ($settings->isTesting()) {
            return self::DEFAULT_LOCALE;
        }

        $supported_locales = $settings['locale']['supported'];
        $try_locales = [];

        // Prefer user-based profile locale.
        if ($this->user !== null && !empty($this->user->getLocale()) && 'default' !== $this->user->getLocale()) {
            $try_locales[] = $this->user->getLocale();
        }

        // Attempt to load from browser headers.
        if ($request instanceof Request) {
            $server_params = $request->getServerParams();
            $browser_locale = Locale::acceptFromHttp($server_params['HTTP_ACCEPT_LANGUAGE'] ?? null);

            if (!empty($browser_locale)) {
                $try_locales[] = substr($browser_locale, 0, 5) . '.UTF-8';
            }
        }

        // Attempt to load from environment variable.
        $env_locale = getenv('LANG');
        if (!empty($env_locale)) {
            $try_locales[] = substr($env_locale, 0, 5) . '.UTF-8';
        }

        foreach ($try_locales as $exact_locale) {
            // Prefer exact match.
            if (isset($supported_locales[$exact_locale])) {
                return $exact_locale;
            }

            // Use approximate match if available.
            foreach ($supported_locales as $lang_code => $lang_name) {
                if (strpos($exact_locale, substr($lang_code, 0, 2)) === 0) {
                    return $lang_code;
                }
            }
        }

        // Default to system option.
        return self::DEFAULT_LOCALE;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale ?? self::DEFAULT_LOCALE;
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
            $instance_name = $this->settingsRepo->getSetting(Entity\Settings::INSTANCE_NAME, '');
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
        return $this->settingsRepo->getSetting(Entity\Settings::PUBLIC_THEME, self::DEFAULT_THEME);
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicCss()
    {
        return (string)$this->settingsRepo->getSetting(Entity\Settings::CUSTOM_CSS_PUBLIC, '');
    }

    /**
     * Return the administrator-supplied custom JS for public (minimal layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomPublicJs()
    {
        return (string)$this->settingsRepo->getSetting(Entity\Settings::CUSTOM_JS_PUBLIC, '');
    }

    /**
     * Return the administrator-supplied custom CSS for internal (full layout) pages, if specified.
     *
     * @return string
     */
    public function getCustomInternalCss()
    {
        return (string)$this->settingsRepo->getSetting(Entity\Settings::CUSTOM_CSS_INTERNAL, '');
    }

    /**
     * Return whether to show or hide album art on public pages.
     *
     * @return bool
     */
    public function hideAlbumArt(): bool
    {
        return (bool)$this->settingsRepo->getSetting(Entity\Settings::HIDE_ALBUM_ART, false);
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     *
     * @return UriInterface
     */
    public function getDefaultAlbumArtUrl(): UriInterface
    {
        $custom_url = trim($this->settingsRepo->getSetting(Entity\Settings::DEFAULT_ALBUM_ART_URL));

        if (!empty($custom_url)) {
            return new Uri($custom_url);
        }

        return new Uri('/static/img/generic_song.jpg');
    }

    /**
     * Return the calculated page title given branding settings and the application environment.
     *
     * @param string|null $title
     *
     * @return string
     */
    public function getPageTitle($title = null): string
    {
        $settings = Settings::getInstance();

        if (!$this->hideProductName()) {
            if ($title) {
                $title .= ' - ' . $settings[Settings::APP_NAME];
            } else {
                $title = $settings[Settings::APP_NAME];
            }
        }

        if (!$settings->isProduction()) {
            $title = '(' . ucfirst($settings[Settings::APP_ENV]) . ') ' . $title;
        }

        return $title;
    }

    /**
     * Return whether to show or hide the AzuraCast name from public-facing pages.
     *
     * @return bool
     */
    public function hideProductName(): bool
    {
        return (bool)$this->settingsRepo->getSetting(Entity\Settings::HIDE_PRODUCT_NAME, false);
    }

    /**
     * @return bool
     */
    public function useWebSocketsForNowPlaying(): bool
    {
        if (!NChan::isSupported()) {
            return false;
        }

        return (bool)$this->settingsRepo->getSetting(Entity\Settings::NOWPLAYING_USE_WEBSOCKETS, false);
    }

}
