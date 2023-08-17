<?php

declare(strict_types=1);

namespace App\Enums;

use App\Environment;
use App\Http\ServerRequest;
use Locale;
use PhpMyAdmin\MoTranslator\Loader;
use Psr\Http\Message\ServerRequestInterface;

enum SupportedLocales: string
{
    case English = 'en_US.UTF-8';
    case Czech = 'cs_CZ.UTF-8';
    case German = 'de_DE.UTF-8';
    case Spanish = 'es_ES.UTF-8';
    case French = 'fr_FR.UTF-8';
    case Greek = 'el_GR.UTF-8';
    case Italian = 'it_IT.UTF-8';
    case Hungarian = 'hu_HU.UTF-8';
    case Dutch = 'nl_NL.UTF-8';
    case Polish = 'pl_PL.UTF-8';
    case Portuguese = 'pt_PT.UTF-8';
    case BrazilianPortuguese = 'pt_BR.UTF-8';
    case Russian = 'ru_RU.UTF-8';
    case Swedish = 'sv_SE.UTF-8';
    case Turkish = 'tr_TR.UTF-8';
    case SimplifiedChinese = 'zh_CN.UTF-8';
    case Korean = 'ko_KR.UTF-8';

    public function getLocalName(): string
    {
        return match ($this) {
            self::English => 'English (Default)',
            self::Czech => 'čeština',
            self::German => 'Deutsch',
            self::Spanish => 'Español',
            self::French => 'Français',
            self::Greek => 'ελληνικά',
            self::Italian => 'Italiano',
            self::Hungarian => 'magyar',
            self::Dutch => 'Nederlands',
            self::Polish => 'Polski',
            self::Portuguese => 'Português',
            self::BrazilianPortuguese => 'Português do Brasil',
            self::Russian => 'Русский язык',
            self::Swedish => 'Svenska',
            self::Turkish => 'Türkçe',
            self::SimplifiedChinese => '簡化字',
            self::Korean => '한국어',
        };
    }

    /**
     * @return string A shortened locale (minus .UTF-8).
     */
    public function getLocaleWithoutEncoding(): string
    {
        return self::stripLocaleEncoding($this);
    }

    /**
     * @return string The first two characters of the locale (for HTML `lang` attribute).
     */
    public function getHtmlLang(): string
    {
        return strtolower(substr($this->value, 0, 2));
    }

    public function register(Environment $environment): void
    {
        // Skip translation file reading for default locale.
        if ($this !== self::default()) {
            $translator = Loader::getInstance();
            $translator->setlocale($this->value);
            $translator->textdomain('default');
            $translator->bindtextdomain('default', $environment->getBaseDirectory() . '/translations');
        }

        Loader::loadFunctions();
    }

    public static function default(): self
    {
        return self::English;
    }

    public static function getValidLocale(array|string|null $possibleLocales): self
    {
        if (null !== $possibleLocales) {
            if (is_string($possibleLocales)) {
                $possibleLocales = [$possibleLocales];
            }

            foreach ($possibleLocales as $locale) {
                $locale = self::ensureLocaleEncoding($locale);

                // Prefer exact match.
                $exactLocale = self::tryFrom($locale);
                if (null !== $exactLocale) {
                    return $exactLocale;
                }

                // Use approximate match if available.
                foreach (self::cases() as $supportedLocale) {
                    if (str_starts_with($locale, substr($supportedLocale->value, 0, 2))) {
                        return $supportedLocale;
                    }
                }
            }
        }

        return self::default();
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

        $serverParams = $request->getServerParams();
        $browserLocale = Locale::acceptFromHttp($serverParams['HTTP_ACCEPT_LANGUAGE'] ?? '');

        if (!empty($browserLocale)) {
            if (2 === strlen($browserLocale)) {
                $browserLocale = strtolower($browserLocale) . '_' . strtoupper($browserLocale);
            }

            $possibleLocales[] = substr($browserLocale, 0, 5) . '.UTF-8';
        }

        // Attempt to load from environment variable.
        $envLang = $environment->getLang();
        if (null !== $envLang) {
            $possibleLocales[] = $envLang;
        }

        $locale = self::getValidLocale($possibleLocales);
        $locale->register($environment);
        return $locale;
    }

    public static function createForCli(
        Environment $environment
    ): self {
        $locale = self::getValidLocale($environment->getLang());
        $locale->register($environment);
        return $locale;
    }

    public static function stripLocaleEncoding(string|self $locale): string
    {
        if ($locale instanceof self) {
            $locale = $locale->value;
        }
        if (str_contains($locale, '.')) {
            return explode('.', $locale, 2)[0];
        }
        return $locale;
    }

    public static function ensureLocaleEncoding(string|self $locale): string
    {
        return self::stripLocaleEncoding($locale) . '.UTF-8';
    }
}
