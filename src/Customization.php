<?php

namespace App;

use App\Entity;
use App\Http\ServerRequest;
use App\Service\NChan;
use Gettext\Translator;
use Locale;
use Psr\Http\Message\ServerRequestInterface;

class Customization
{
    public const DEFAULT_LOCALE = 'en_US.UTF-8';
    public const DEFAULT_THEME = 'light';

    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';

    protected ?Entity\User $user = null;

    protected Entity\Settings $settings;

    protected Environment $environment;

    protected string $locale = self::DEFAULT_LOCALE;

    protected string $theme = self::DEFAULT_THEME;

    protected string $publicTheme = self::DEFAULT_THEME;

    protected string $instanceName = '';

    public function __construct(
        Entity\Repository\SettingsRepository $settingsRepo,
        Environment $environment,
        ServerRequestInterface $request
    ) {
        $this->settings = $settingsRepo->readSettings();
        $this->environment = $environment;

        $this->instanceName = $this->settings->getInstanceName() ?? '';

        // Register current user
        $this->user = $request->getAttribute(ServerRequest::ATTR_USER);

        $this->locale = $this->initLocale($request);

        // Register current theme
        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['theme'])) {
            $this->publicTheme = $this->theme = $queryParams['theme'];
        } else {
            $this->publicTheme = $this->settings->getPublicTheme() ?? $this->publicTheme;

            if (null !== $this->user && !empty($this->user->getTheme())) {
                $this->theme = (string)$this->user->getTheme();
            }
        }

        // Set up the PHP translator
        $translator = new Translator();

        $locale_base = $environment->getBaseDirectory() . '/resources/locale/compiled';
        $locale_path = $locale_base . '/' . $this->locale . '.php';

        if (file_exists($locale_path)) {
            $translator->loadTranslations($locale_path);
        }

        $translator->register();

        // Register translation superglobal functions
        setlocale(LC_ALL, $this->locale);
    }

    /**
     * Return the user-customized, browser-specified or system default locale.
     *
     * @param ServerRequestInterface|null $request
     */
    protected function initLocale(?ServerRequestInterface $request = null): string
    {
        $supported_locales = $this->environment->getSupportedLocales();
        $try_locales = [];

        // Prefer user-based profile locale.
        if ($this->user !== null && !empty($this->user->getLocale()) && 'default' !== $this->user->getLocale()) {
            $try_locales[] = $this->user->getLocale();
        }

        // Attempt to load from browser headers.
        if ($request instanceof ServerRequestInterface) {
            $server_params = $request->getServerParams();
            $browser_locale = Locale::acceptFromHttp($server_params['HTTP_ACCEPT_LANGUAGE'] ?? null);

            if (!empty($browser_locale)) {
                $try_locales[] = substr($browser_locale, 0, 5) . '.UTF-8';
            }
        }

        // Attempt to load from environment variable.
        $envLocale = $this->environment->getLang();
        if (!empty($envLocale)) {
            $try_locales[] = substr($envLocale, 0, 5) . '.UTF-8';
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string A shortened locale (minus .UTF-8) for use in Vue.
     */
    public function getVueLocale(): string
    {
        return json_encode(substr($this->getLocale(), 0, 5), JSON_THROW_ON_ERROR);
    }

    /**
     * Returns the user-customized or system default theme.
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Get the instance name for this AzuraCast instance.
     */
    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * Get the theme name to be used in public (non-logged-in) pages.
     */
    public function getPublicTheme(): string
    {
        return $this->publicTheme;
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     */
    public function getCustomPublicCss(): string
    {
        return $this->settings->getPublicCustomCss() ?? '';
    }

    /**
     * Return the administrator-supplied custom JS for public (minimal layout) pages, if specified.
     */
    public function getCustomPublicJs(): string
    {
        return $this->settings->getPublicCustomJs() ?? '';
    }

    /**
     * Return the administrator-supplied custom CSS for internal (full layout) pages, if specified.
     */
    public function getCustomInternalCss(): string
    {
        return $this->settings->getInternalCustomCss() ?? '';
    }

    /**
     * Return whether to show or hide album art on public pages.
     */
    public function hideAlbumArt(): bool
    {
        return $this->settings->getHideAlbumArt();
    }

    /**
     * Return the calculated page title given branding settings and the application environment.
     *
     * @param string|null $title
     */
    public function getPageTitle($title = null): string
    {
        if (!$this->hideProductName()) {
            if ($title) {
                $title .= ' - ' . $this->environment->getAppName();
            } else {
                $title = $this->environment->getAppName();
            }
        }

        if (!$this->environment->isProduction()) {
            $title = '(' . ucfirst($this->environment->getAppEnvironment()) . ') ' . $title;
        }

        return $title;
    }

    /**
     * Return whether to show or hide the AzuraCast name from public-facing pages.
     */
    public function hideProductName(): bool
    {
        return $this->settings->getHideProductName();
    }

    public function useWebSocketsForNowPlaying(): bool
    {
        if (!NChan::isSupported()) {
            return false;
        }

        return $this->settings->getEnableWebsockets();
    }

    /**
     * Initialize the CLI without instantiating the Doctrine DB stack (allowing cache clearing, etc.).
     */
    public static function initCli(): void
    {
        $translator = new Translator();
        $translator->register();
    }
}
