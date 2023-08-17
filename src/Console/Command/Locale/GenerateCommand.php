<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Console\Command\Traits\PassThruProcess;
use App\Container\EnvironmentAwareTrait;
use App\Enums\SupportedLocales;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Scanner\PhpScanner;
use Gettext\Translations;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:locale:generate',
    description: 'Generate the translation locale file.',
    aliases: ['locale:generate']
)]
final class GenerateCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;
    use PassThruProcess;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generate Locales');

        $exportDir = $this->environment->getBaseDirectory() . '/translations';

        $translations = Translations::create('default');
        $destFile = $exportDir . '/default.pot';

        // Run the JS generator
        $this->passThruProcess(
            $io,
            ['npm', 'run', 'generate-locales'],
            $this->environment->getBaseDirectory() . '/frontend'
        );

        // Import the JS-generated files if they exist
        $frontendJsFile = $exportDir . '/frontend.pot';

        if (is_file($frontendJsFile)) {
            $translations = (new PoLoader())->loadFile($frontendJsFile, $translations);
            @unlink($frontendJsFile);
        }

        // Find all PHP/PHTML files in the application's code.
        $translatableFolders = [
            $this->environment->getBaseDirectory() . '/src',
            $this->environment->getBaseDirectory() . '/config',
            $this->environment->getViewsDirectory(),
        ];

        $phpScanner = new PhpScanner($translations);
        $phpScanner->setDefaultDomain('default');

        foreach ($translatableFolders as $folder) {
            $directory = new RecursiveDirectoryIterator($folder);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.(phtml|php)$/i', RegexIterator::GET_MATCH);

            foreach ($regex as $pathMatch) {
                $path = $pathMatch[0];
                $phpScanner->scanFile($path);
            }
        }

        @unlink($destFile);

        $poGenerator = new PoGenerator();
        $poGenerator->generateFile(
            $translations,
            $destFile
        );

        // Create locale folders if they don't exist already.
        $supportedLocales = SupportedLocales::cases();
        $defaultLocale = SupportedLocales::default();

        foreach ($supportedLocales as $supportedLocale) {
            if ($supportedLocale === $defaultLocale) {
                continue;
            }

            $localeDir = $exportDir . '/' . $supportedLocale->value . '/LC_MESSAGES';
            if (!is_dir($localeDir)) {
                /** @noinspection MkdirRaceConditionInspection */
                mkdir($localeDir, 0777, true);
            }
        }


        $io->success('Locales generated.');
        return 0;
    }
}
