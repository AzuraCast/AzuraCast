<?php

namespace App;

use App\Http\ServerRequest;
use Gettext\Translator;
use Psr\Http\Message\ServerRequestInterface;

class Locale
{
    public const DEFAULT_LOCALE = 'en_US.UTF-8';

    protected string $locale = self::DEFAULT_LOCALE;

    public function __construct(
        protected Environment $environment,
        string|array $possibleLocales
    ) {
        if (is_string($possibleLocales)) {
            $possibleLocales = [$possibleLocales];
        }

        $this->locale = $this->getValidLocale($possibleLocales);
    }

    protected function getValidLocale(array $possibleLocales): string
    {
        $supportedLocales = $this->environment->getSupportedLocales();

        foreach ($possibleLocales as $locale) {
            $locale = self::ensureLocaleEncoding($locale);

            // Prefer exact match.
            if (isset($supportedLocales[$locale])) {
                return $locale;
            }

            // Use approximate match if available.
            foreach ($supportedLocales as $langCode => $langName) {
                if (str_starts_with($locale, substr($langCode, 0, 2))) {
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
     * @return string A shortened locale (minus .UTF-8).
     */
    public function getLocaleWithoutEncoding(): string
    {
        return self::stripLocaleEncoding($this->locale);
    }

    public function setLocale(string $newLocale = self::DEFAULT_LOCALE): void
    {
        $this->locale = $newLocale;
    }

    public function createTranslator(): Translator
    {
        $translator = new Translator();

        $localeBase = $this->environment->getBaseDirectory() . '/resources/locale/compiled';
        $localePath = $localeBase . '/' . $this->locale . '.php';

        if (file_exists($localePath)) {
            $translator->loadTranslations($localePath);
        }

        return $translator;
    }

    public function register(): void
    {
        $translator = $this->createTranslator();
        $translator->register();

        // Register translation superglobal functions
        setlocale(LC_ALL, $this->locale);
    }

    public function __toString(): string
    {
        return $this->locale;
    }

    public static function createFromRequest(
        Environment $environment,
        ServerRequestInterface $request
    ): self {
        $possibleLocales = [];

        // Prefer user-based profile locale.
        $user = $request->getAttribute(ServerRequest::ATTR_USER);
        if (null !== $user && !empty($user->getLocale()) && 'default' !== $user->getLocale()) {
            $possibleLocales[] = $user->getLocale();
        }

        $server_params = $request->getServerParams();
        $browser_locale = \Locale::acceptFromHttp($server_params['HTTP_ACCEPT_LANGUAGE'] ?? null);

        if (!empty($browser_locale)) {
            if (2 === strlen($browser_locale)) {
                $browser_locale = strtolower($browser_locale) . '_' . strtoupper($browser_locale);
            }

            $possibleLocales[] = substr($browser_locale, 0, 5) . '.UTF-8';
        }

        // Attempt to load from environment variable.
        $possibleLocales[] = $environment->getLang();

        return new self($environment, $possibleLocales);
    }

    public static function createForCli(
        Environment $environment
    ): self {
        return new self(
            $environment,
            $environment->getLang()
        );
    }

    public static function stripLocaleEncoding(string $locale): string
    {
        if (str_contains($locale, '.')) {
            return explode('.', $locale, 2)[0];
        }
        return $locale;
    }

    public static function ensureLocaleEncoding(string $locale): string
    {
        return self::stripLocaleEncoding($locale) . '.UTF-8';
    }
}
