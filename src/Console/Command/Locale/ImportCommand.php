<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Enums\SupportedLocales;
use App\Environment;
use Gettext\Generator\MoGenerator;
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
    public function __construct(
        private readonly Environment $environment
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Locales');

        $localesBase = $this->environment->getBaseDirectory() . '/translations';

        $supportedLocales = SupportedLocales::cases();
        $defaultLocale = SupportedLocales::default();

        $poLoader = new PoLoader();
        $moGenerator = new MoGenerator();

        foreach ($supportedLocales as $supportedLocale) {
            if ($supportedLocale === $defaultLocale) {
                continue;
            }

            $localeFolder = $localesBase . '/' . $supportedLocale->value . '/LC_MESSAGES';
            $localeSource = $localeFolder . '/default.po';
            $localeDest = $localeFolder . '/default.mo';

            if (is_file($localeSource)) {
                $translations = $poLoader->loadFile($localeSource);
                $moGenerator->generateFile($translations, $localeDest);

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
