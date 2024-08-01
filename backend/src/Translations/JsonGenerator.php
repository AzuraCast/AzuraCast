<?php

declare(strict_types=1);

namespace App\Translations;

use App\Enums\SupportedLocales;
use Gettext\Generator\Generator;
use Gettext\Translation;
use Gettext\Translations;

final class JsonGenerator extends Generator
{
    public function __construct(
        private readonly SupportedLocales $locale
    ) {
    }

    public function generateString(Translations $translations): string
    {
        $array = $this->generateArray($translations);

        return json_encode($array, JSON_PRETTY_PRINT) ?: '';
    }

    public function generateArray(Translations $translations): array
    {
        $pluralForm = $translations->getHeaders()->getPluralForm();
        $pluralSize = is_array($pluralForm) ? (int)($pluralForm[0] - 1) : null;
        $messages = [];

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            if (!$translation->getTranslation() || $translation->isDisabled()) {
                continue;
            }

            $original = $translation->getOriginal();

            if (self::hasPluralTranslations($translation)) {
                $messages[$original] = $translation->getPluralTranslations($pluralSize);
                array_unshift($messages[$original], $translation->getTranslation());
            } else {
                $messages[$original] = $translation->getTranslation();
            }
        }

        return [
            $this->locale->getLocaleWithoutEncoding() => $messages,
        ];
    }

    private static function hasPluralTranslations(Translation $translation): bool
    {
        return implode('', $translation->getPluralTranslations()) !== '';
    }
}
