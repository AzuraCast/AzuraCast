<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Environment;
use App\Locale;
use Gettext\Translation;
use Gettext\Translations;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Environment $environment
    ): int {
        $io->title('Import Locales');

        $locales = Locale::SUPPORTED_LOCALES;
        $locale_base = $environment->getBaseDirectory() . '/resources/locale';

        $jsTranslations = [];

        foreach ($locales as $locale_key => $locale_name) {
            $locale_source = $locale_base . '/' . $locale_key . '/LC_MESSAGES/default.po';

            if (is_file($locale_source)) {
                $translations = Translations::fromPoFile($locale_source);

                // Temporary inclusion of frontend translations
                $frontendTranslations = $locale_base . '/' . $locale_key . '/LC_MESSAGES/frontend.po';
                if (is_file($frontendTranslations)) {
                    $frontendTranslations = Translations::fromPoFile($frontendTranslations);
                    $translations->mergeWith($frontendTranslations);
                }

                $locale_dest = $locale_base . '/compiled/' . $locale_key . '.php';
                $translations->toPhpArrayFile($locale_dest);

                $localeJsKey = str_replace('.UTF-8', '', $locale_key);

                /** @var Translation $translation */
                foreach ($translations as $translation) {
                    if ($translation->isDisabled() || !$translation->hasTranslation()) {
                        continue;
                    }

                    $jsTranslations[$localeJsKey][$translation->getOriginal()] = $translation->getTranslation();
                }

                $io->writeln(__('Imported locale: %s', $locale_key . ' (' . $locale_name . ')'));
            }
        }

        $jsTranslations = json_encode(
            $jsTranslations,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $jsTranslationsPath = $locale_base . '/translations.json';

        file_put_contents($jsTranslationsPath, $jsTranslations);

        $io->success('Locales imported.');
        return 0;
    }
}
