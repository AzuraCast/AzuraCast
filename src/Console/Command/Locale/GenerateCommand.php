<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Environment;
use Gettext\Translations;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Environment $environment
    ): int {
        $io->title('Generate Locales');

        $exportDir = $environment->getBaseDirectory() . '/resources/locale';

        $dest_file = $exportDir . '/default.pot';
        $frontendFile = $exportDir . '/frontend.pot';

        $translations = new Translations();

        if (file_exists($frontendFile)) {
            $translations->addFromPoFile($frontendFile);
        }

        // Find all PHP/PHTML files in the application's code.
        $translatable_folders = [
            $environment->getBaseDirectory() . '/src',
            $environment->getBaseDirectory() . '/config',
            $environment->getViewsDirectory(),
        ];

        foreach ($translatable_folders as $folder) {
            $directory = new RecursiveDirectoryIterator($folder);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.(phtml|php)$/i', RecursiveRegexIterator::GET_MATCH);

            foreach ($regex as $path_match) {
                $path = $path_match[0];
                $translations->addFromPhpCodeFile($path);
            }
        }

        $translations->toPoFile($dest_file);

        @unlink($frontendFile);

        $io->success('Locales generated.');
        return 0;
    }
}
