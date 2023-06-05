<?php

declare(strict_types=1);

namespace App;

use App\Assets\BackgroundCustomAsset;
use App\Assets\BrowserIconCustomAsset;
use App\Entity;
use App\Enums\SupportedLocales;
use App\Enums\SupportedThemes;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Psr\Http\Message\ServerRequestInterface;

final class Customization
{
    use RequestAwareTrait;

    private ?Entity\User $user = null;

    private Entity\Settings $settings;

    private SupportedLocales $locale;

    private SupportedThemes $theme;

    private SupportedThemes $publicTheme;

    private string $instanceName = '';

    public function __construct(
        private readonly Environment $environment,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $this->settings = $settingsRepo->readSettings();

        $this->instanceName = $this->settings->getInstanceName() ?? '';

        $this->user = null;
        $this->theme = SupportedThemes::default();
        $this->publicTheme = $this->settings->getPublicTheme();

        $this->locale = SupportedLocales::default();
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;

        if (null !== $request) {
            // Register current user
            $this->user = $request->getAttribute(ServerRequest::ATTR_USER);

            // Register current theme
            $this->theme = $this->determineTheme($request);
            $this->publicTheme = $this->determineTheme($request, true);

            // Register locale
            $this->locale = SupportedLocales::createFromRequest($this->environment, $request);
        }
    }

    private function determineTheme(
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
            $userTheme = $this->user->getTheme();
            if (null !== $userTheme) {
                return $userTheme;
            }
        }

        return ($isPublicTheme)
            ? $this->settings->getPublicTheme()
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

        $background = new BackgroundCustomAsset($this->environment);
        if ($background->isUploaded()) {
            $backgroundUrl = $background->getUrl();

            $publicCss .= <<<CSS
            [data-theme] body.page-minimal {
                background-image: url('{$backgroundUrl}');
            }
            CSS;
        }

        return $publicCss;
    }

    public function getStationCustomPublicCss(Entity\Station $station): string
    {
        $publicCss = $station->getBrandingConfig()->getPublicCustomCss() ?? '';

        $background = new BackgroundCustomAsset($this->environment, $station);
        if ($background->isUploaded()) {
            $backgroundUrl = $background->getUrl();

            $publicCss .= <<<CSS
            [data-theme] body.page-minimal {
                background-image: url('{$backgroundUrl}');
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

    public function getStationCustomPublicJs(Entity\Station $station): string
    {
        return $station->getBrandingConfig()->getPublicCustomJs() ?? '';
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
        return (new BrowserIconCustomAsset($this->environment))->getUrlForSize($size);
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
            $title = '(' . $this->environment->getAppEnvironmentEnum()->getName() . ') ' . $title;
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

    public function useStaticNowPlaying(): bool
    {
        return $this->settings->getEnableStaticNowPlaying();
    }
}
