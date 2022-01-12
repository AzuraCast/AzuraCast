<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Enums\SupportedLocales;
use App\Environment;
use Gettext\Translation;
use Gettext\Translations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:locale:import',
    description: 'Convert translated locale files into PHP arrays.',
    aliases: ['locale:import']
)]
class ImportCommand extends CommandAbstract
{
    public function __construct(
        protected Environment $environment
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Locales');

        $localeBase = $this->environment->getBaseDirectory() . '/resources/locale';

        $jsTranslations = [];

        $supportedLocales = SupportedLocales::cases();
        $defaultLocale = SupportedLocales::default();

        foreach ($supportedLocales as $supportedLocale) {
            if ($supportedLocale === $defaultLocale) {
                continue;
            }

            $locale_source = $localeBase . '/' . $supportedLocale->value . '/LC_MESSAGES/default.po';

            if (is_file($locale_source)) {
                $translations = Translations::fromPoFile($locale_source);

                // Temporary inclusion of frontend translations
                $frontendTranslations = $localeBase . '/' . $supportedLocale->value . '/LC_MESSAGES/frontend.po';
                if (is_file($frontendTranslations)) {
                    $frontendTranslations = Translations::fromPoFile($frontendTranslations);
                    $translations->mergeWith($frontendTranslations);
                }

                $locale_dest = $localeBase . '/compiled/' . $supportedLocale->value . '.php';
                $translations->toPhpArrayFile($locale_dest);

                $localeJsKey = str_replace('.UTF-8', '', $supportedLocale->value);

                /** @var Translation $translation */
                foreach ($translations as $translation) {
                    if ($translation->isDisabled() || !$translation->hasTranslation()) {
                        continue;
                    }

                    if ($translation->hasPlural()) {
                        $string = [
                            $translation->getTranslation(),
                        ];

                        $pluralStrings = $translation->getPluralTranslations();
                        if (count($pluralStrings) > 0) {
                            $string = array_merge($string, $pluralStrings);
                        }
                    } else {
                        $string = $translation->getTranslation();
                    }

                    $jsTranslations[$localeJsKey][$translation->getOriginal()] = $string;
                }

                ksort($jsTranslations[$localeJsKey]);

                $io->writeln(
                    __('Imported locale: %s', $supportedLocale->value . ' (' . $supportedLocale->getLocalName() . ')')
                );
            }
        }

        $jsTranslations = json_encode(
            $jsTranslations,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $jsTranslationsPath = $localeBase . '/translations.json';

        file_put_contents($jsTranslationsPath, $jsTranslations);

        $io->success('Locales imported.');
        return 0;
    }
}
