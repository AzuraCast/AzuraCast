<?php

declare(strict_types=1);

namespace App;

use App\Http\ServerRequest;
use Gettext\Translator;
use Psr\Http\Message\ServerRequestInterface;

class Locale
{
    public const DEFAULT_LOCALE = 'en_US.UTF-8';

    public const SUPPORTED_LOCALES = [
        'en_US.UTF-8' => 'English (Default)',
        'cs_CZ.UTF-8' => 'čeština',             // Czech
        'de_DE.UTF-8' => 'Deutsch',             // German
        'es_ES.UTF-8' => 'Español',             // Spanish
        'fr_FR.UTF-8' => 'Français',            // French
        'el_GR.UTF-8' => 'ελληνικά',            // Greek
        'it_IT.UTF-8' => 'Italiano',            // Italian
        'hu_HU.UTF-8' => 'magyar',              // Hungarian
        'nl_NL.UTF-8' => 'Nederlands',          // Dutch
        'pl_PL.UTF-8' => 'Polski',              // Polish
        'pt_PT.UTF-8' => 'Português',           // Portuguese
        'pt_BR.UTF-8' => 'Português do Brasil', // Brazilian Portuguese
        'ru_RU.UTF-8' => 'Русский язык',        // Russian
        'sv_SE.UTF-8' => 'Svenska',             // Swedish
        'tr_TR.UTF-8' => 'Türkçe',              // Turkish
        'zh_CN.UTF-8' => '簡化字',               // Simplified Chinese
        'ko_KR.UTF-8' => '한국어',               // Korean (South Korean)
    ];

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
        $supportedLocales = self::SUPPORTED_LOCALES;

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
        $browser_locale = \Locale::acceptFromHttp($server_params['HTTP_ACCEPT_LANGUAGE'] ?? '');

        if (!empty($browser_locale)) {
            if (2 === strlen($browser_locale)) {
                $browser_locale = strtolower($browser_locale) . '_' . strtoupper($browser_locale);
            }

            $possibleLocales[] = substr($browser_locale, 0, 5) . '.UTF-8';
        }

        // Attempt to load from environment variable.
        $envLang = $environment->getLang();
        if (null !== $envLang) {
            $possibleLocales[] = $envLang;
        }

        return new self($environment, $possibleLocales);
    }

    public static function createForCli(
        Environment $environment
    ): self {
        return new self(
            $environment,
            $environment->getLang() ?? self::DEFAULT_LOCALE
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
