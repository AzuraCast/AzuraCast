<?php

namespace App;

use App\Http\ServerRequest;
use Gettext\Translator;
use Psr\Http\Message\ServerRequestInterface;

class Locale
{
    public const DEFAULT_LOCALE = 'en_US.UTF-8';

    protected Environment $environment;

    protected ?ServerRequestInterface $request = null;

    protected string $locale = self::DEFAULT_LOCALE;

    public function __construct(
        Environment $environment,
        ?ServerRequestInterface $request = null
    ) {
        $this->environment = $environment;
        $this->request = $request;

        $this->locale = $this->determineLocale();
    }

    protected function determineLocale(): string
    {
        $possibleLocales = [];

        // Attempt to load from request if provided.
        if ($this->request instanceof ServerRequestInterface) {
            // Prefer user-based profile locale.
            $user = $this->request->getAttribute(ServerRequest::ATTR_USER);
            if (null !== $user && !empty($user->getLocale()) && 'default' !== $user->getLocale()) {
                $possibleLocales[] = $user->getLocale();
            }

            $server_params = $this->request->getServerParams();
            $browser_locale = \Locale::acceptFromHttp($server_params['HTTP_ACCEPT_LANGUAGE'] ?? null);

            if (!empty($browser_locale)) {
                if (2 === strlen($browser_locale)) {
                    $browser_locale = strtolower($browser_locale) . '_' . strtoupper($browser_locale);
                }

                $possibleLocales[] = substr($browser_locale, 0, 5) . '.UTF-8';
            }
        }

        // Attempt to load from environment variable.
        $envLocale = $this->environment->getLang();
        if (!empty($envLocale)) {
            $possibleLocales[] = substr($envLocale, 0, 5) . '.UTF-8';
        }

        return $this->getValidLocale($possibleLocales);
    }

    protected function getValidLocale(array $possibleLocales): string
    {
        $supportedLocales = $this->environment->getSupportedLocales();

        foreach ($possibleLocales as $locale) {
            // Prefer exact match.
            if (isset($supportedLocales[$locale])) {
                return $locale;
            }

            // Use approximate match if available.
            foreach ($supportedLocales as $langCode => $langName) {
                if (strpos($locale, substr($langCode, 0, 2)) === 0) {
                    return $langCode;
                }
            }
        }

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
        return json_encode(substr($this->locale, 0, 5), JSON_THROW_ON_ERROR);
    }

    public function setLocale(string $newLocale = self::DEFAULT_LOCALE): void
    {
        $this->locale = $newLocale;
    }

    public function register(): void
    {
        $translator = new Translator();

        $localeBase = $this->environment->getBaseDirectory() . '/resources/locale/compiled';
        $localePath = $localeBase . '/' . $this->locale . '.php';
        if (file_exists($localePath)) {
            $translator->loadTranslations($localePath);
        }

        $translator->register();

        // Register translation superglobal functions
        setlocale(LC_ALL, $this->locale);
    }
}
