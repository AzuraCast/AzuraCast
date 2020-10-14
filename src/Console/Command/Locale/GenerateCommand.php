<?php

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Settings;
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
        Settings $settings
    ): int {
        $io->title('Generate Locales');

        $dest_file = $settings[Settings::BASE_DIR] . '/resources/locale/default.pot';
        $translations = new Translations();

        // Find all PHP/PHTML files in the application's code.
        $translatable_folders = [
            $settings[Settings::BASE_DIR] . '/src',
            $settings[Settings::BASE_DIR] . '/config',
            $settings[Settings::VIEWS_DIR],
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

        $io->success('Locales generated.');
        return 0;
    }
}
