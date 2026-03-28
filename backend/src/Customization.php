<?php

declare(strict_types=1);

namespace App;

use App\Assets\AlbumArtCustomAsset;
use App\Assets\BackgroundCustomAsset;
use App\Assets\BrowserIconCustomAsset;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Entity\Station;
use App\Enums\SupportedLocales;
use App\Enums\SupportedThemes;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;
use Psr\Http\Message\UriInterface;

final class Customization
{
    use RequestAwareTrait;
    use EnvironmentAwareTrait;

    private readonly Settings $settings;

    private SupportedLocales $locale;

    private ?SupportedThemes $publicTheme;

    private string $instanceName;

    public function __construct(
        SettingsRepository $settingsRepo,
        private readonly AlbumArtCustomAsset $albumArtCustomAsset,
        private readonly BrowserIconCustomAsset $browserIconCustomAsset,
        private readonly BackgroundCustomAsset $backgroundCustomAsset,
    ) {
        $this->settings = $settingsRepo->readSettings();
        $this->instanceName = $this->settings->instance_name ?? '';
        $this->publicTheme = $this->settings->public_theme;
        $this->locale = SupportedLocales::default();
    }

    public function setRequest(?ServerRequest $request): void
    {
        $this->request = $request;

        if (null !== $request) {
            // Register current theme
            $queryParams = $request->getQueryParams();
            if (!empty($queryParams['theme'])) {
                $theme = SupportedThemes::tryFrom($queryParams['theme']);
                if (null !== $theme && $theme !== SupportedThemes::Browser) {
                    $this->publicTheme = $theme;
                }
            }

            // Register locale
            $this->locale = SupportedLocales::createFromRequest($this->environment, $request);
        }
    }

    public function getLocale(): SupportedLocales
    {
        return $this->locale;
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
    public function getPublicTheme(): ?SupportedThemes
    {
        return (SupportedThemes::Browser !== $this->publicTheme)
            ? $this->publicTheme
            : null;
    }

    /**
     * Return the administrator-supplied custom CSS for public (minimal layout) pages, if specified.
     */
    public function getCustomPublicCss(): string
    {
        $publicCss = $this->settings->public_custom_css ?? '';

        if ($this->backgroundCustomAsset->isUploaded()) {
            $backgroundUrl = $this->backgroundCustomAsset->getUrl();

            $publicCss .= <<<CSS
            [data-bs-theme] body.page-minimal {
                background-image: url('{$backgroundUrl}');
            }
            CSS;
        }

        return $publicCss;
    }

    public function getStationCustomPublicCss(Station $station): string
    {
        $publicCss = $station->branding_config->public_custom_css ?? '';

        if ($this->backgroundCustomAsset->isUploaded($station)) {
            $backgroundUrl = $this->backgroundCustomAsset->getUrl($station);

            $publicCss .= <<<CSS
            [data-bs-theme] body.page-minimal {
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
        return $this->settings->public_custom_js ?? '';
    }

    public function getStationCustomPublicJs(Station $station): string
    {
        return $station->branding_config->public_custom_js ?? '';
    }

    /**
     * Return the administrator-supplied custom CSS for internal (full layout) pages, if specified.
     */
    public function getCustomInternalCss(): string
    {
        return $this->settings->internal_custom_css ?? '';
    }

    public function getBrowserIconUrl(int $size = 256): string
    {
        return $this->browserIconCustomAsset->getUrlForSize($size);
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     */
    public function getDefaultAlbumArtUrl(?Station $station = null): UriInterface
    {
        if (null !== $station) {
            if ($this->albumArtCustomAsset->isUploaded($station)) {
                return $this->albumArtCustomAsset->getUri($station);
            }

            $stationCustomUri = $station->branding_config->getDefaultAlbumArtUrlAsUri();
            if (null !== $stationCustomUri) {
                return $stationCustomUri;
            }
        }

        $customUrl = $this->settings->getDefaultAlbumArtUrlAsUri();
        return $customUrl ?? $this->albumArtCustomAsset->getUri();
    }

    /**
     * Return whether to show or hide album art on public pages.
     */
    public function hideAlbumArt(): bool
    {
        return $this->settings->hide_album_art;
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
        return $this->settings->hide_product_name;
    }

    public function useStaticNowPlaying(): bool
    {
        return $this->settings->enable_static_nowplaying;
    }
}
