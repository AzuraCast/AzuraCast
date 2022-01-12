<?php

declare(strict_types=1);

namespace App;

use App\Assets\AssetFactory;
use App\Entity;
use App\Enums\SupportedLocales;
use App\Enums\SupportedThemes;
use App\Http\ServerRequest;
use App\Service\NChan;
use Psr\Http\Message\ServerRequestInterface;

class Customization
{
    protected ?Entity\User $user = null;

    protected Entity\Settings $settings;

    protected SupportedLocales $locale;

    protected SupportedThemes $theme;

    protected SupportedThemes $publicTheme;

    protected string $instanceName = '';

    public function __construct(
        protected Environment $environment,
        Entity\Repository\SettingsRepository $settingsRepo,
        ServerRequestInterface $request
    ) {
        $this->settings = $settingsRepo->readSettings();

        $this->instanceName = $this->settings->getInstanceName() ?? '';

        // Register current user
        $this->user = $request->getAttribute(ServerRequest::ATTR_USER);

        // Register current theme
        $this->theme = $this->determineTheme($request, false);
        $this->publicTheme = $this->determineTheme($request, true);

        // Register locale
        $this->locale = SupportedLocales::createFromRequest($this->environment, $request);
    }

    protected function determineTheme(
        ServerRequestInterface $request,
        bool $isPublicTheme = false
    ): SupportedThemes {
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['theme'])) {
            $theme = SupportedThemes::tryFrom($queryParams['theme']);
            if (null !== $theme) {
                return $theme;
            }
        }

        if (null !== $this->user) {
            $themeName = $this->user->getTheme();
            if (!empty($themeName)) {
                $theme = SupportedThemes::tryFrom($themeName);
                if (null !== $theme) {
                    return $theme;
                }
            }
        }

        return ($isPublicTheme)
            ? $this->settings->getPublicThemeEnum()
            : SupportedThemes::default();
    }

    public function getLocale(): SupportedLocales
    {
        return $this->locale;
    }

    /**
     * Returns the user-customized or system default theme.
     */
    public function getTheme(): SupportedThemes
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
    public function getPublicTheme(): SupportedThemes
    {
        return $this->publicTheme;
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     */
    public function getCustomPublicCss(): string
    {
        $publicCss = $this->settings->getPublicCustomCss() ?? '';

        $background = AssetFactory::createBackground($this->environment);
        if ($background->isUploaded()) {
            $backgroundUrl = $background->getUrl();

            $publicCss .= <<<CSS
            [data-theme] body.page-minimal {
                background-image: url('${backgroundUrl}');
            }
            CSS;
        }

        return $publicCss;
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

    public function getBrowserIconUrl(int $size = 256): string
    {
        return AssetFactory::createBrowserIcon($this->environment)->getUrlForSize($size);
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
    public function getPageTitle(?string $title = null): string
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

        return $title ?? '';
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

    public function registerLocale(SupportedLocales $locale): void
    {
    }
}
