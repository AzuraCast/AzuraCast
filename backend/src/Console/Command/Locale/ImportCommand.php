<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Enums\SupportedLocales;
use App\Translations\JsonGenerator;
use Gettext\Generator\ArrayGenerator;
use Gettext\Loader\PoLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:locale:import',
    description: 'Convert translated locale files into PHP arrays.',
    aliases: ['locale:import']
)]
final class ImportCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Locales');

        $localesBase = $this->environment->getBaseDirectory() . '/translations';

        $supportedLocales = SupportedLocales::cases();
        $defaultLocale = SupportedLocales::default();

        $poLoader = new PoLoader();
        $arrayGenerator = new ArrayGenerator();

        foreach ($supportedLocales as $supportedLocale) {
            if ($supportedLocale === $defaultLocale) {
                continue;
            }

            $localeFolder = $localesBase . '/' . $supportedLocale->value;
            $localeSource = $localeFolder . '/LC_MESSAGES/default.po';
            $localeDest = $localeFolder . '/LC_MESSAGES/default.php';
            $jsonDest = $localeFolder . '/translations.json';

            if (is_file($localeSource)) {
                $translations = $poLoader->loadFile($localeSource);
                $arrayGenerator->generateFile($translations, $localeDest);

                (new JsonGenerator($supportedLocale))->generateFile($translations, $jsonDest);

                $io->writeln(
                    sprintf(
                        __('Imported locale: %s'),
                        $supportedLocale->value . ' (' . $supportedLocale->getLocalName() . ')'
                    )
                );
            }
        }

        $io->success('Locales imported.');
        return 0;
    }
}
