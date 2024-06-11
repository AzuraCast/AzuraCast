<?php

declare(strict_types=1);

namespace App;

use App\Assets\AssetTypes;
use App\Assets\BrowserIconCustomAsset;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Entity\Station;
use App\Enums\SupportedLocales;
use App\Enums\SupportedThemes;
use App\Http\ServerRequest;
use App\Traits\RequestAwareTrait;

final class Customization
{
    use RequestAwareTrait;
    use EnvironmentAwareTrait;

    private Settings $settings;

    private SupportedLocales $locale;

    private ?SupportedThemes $publicTheme;

    private string $instanceName;

    public function __construct(
        SettingsRepository $settingsRepo
    ) {
        $this->settings = $settingsRepo->readSettings();
        $this->instanceName = $this->settings->getInstanceName() ?? '';
        $this->publicTheme = $this->settings->getPublicTheme();
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
        $publicCss = $this->settings->getPublicCustomCss() ?? '';

        $background = AssetTypes::Background->createObject($this->environment);
        if ($background->isUploaded()) {
            $backgroundUrl = $background->getUrl();

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
        $publicCss = $station->getBrandingConfig()->getPublicCustomCss() ?? '';

        $background = AssetTypes::Background->createObject($this->environment, $station);

        if ($background->isUploaded()) {
            $backgroundUrl = $background->getUrl();

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
        return $this->settings->getPublicCustomJs() ?? '';
    }

    public function getStationCustomPublicJs(Station $station): string
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
        /** @var BrowserIconCustomAsset $browserIcon */
        $browserIcon = AssetTypes::BrowserIcon->createObject($this->environment);

        return $browserIcon->getUrlForSize($size);
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

    public function enableAdvancedFeatures(): bool
    {
        return $this->settings->getEnableAdvancedFeatures();
    }

    public function useStaticNowPlaying(): bool
    {
        return $this->settings->getEnableStaticNowPlaying();
    }
}
