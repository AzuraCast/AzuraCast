<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Environment;
use App\Locale;
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

        foreach ($locales as $locale_key => $locale_name) {
            $locale_source = $locale_base . '/' . $locale_key . '/LC_MESSAGES/default.po';

            if (file_exists($locale_source)) {
                $translations = Translations::fromPoFile($locale_source);

                $locale_dest = $locale_base . '/compiled/' . $locale_key . '.php';
                $translations->toPhpArrayFile($locale_dest);

                $io->writeln(__('Imported locale: %s', $locale_key . ' (' . $locale_name . ')'));
            }
        }

        $io->success('Locales imported.');
        return 0;
    }
}
