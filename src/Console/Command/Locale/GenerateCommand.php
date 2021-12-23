<?php

declare(strict_types=1);

namespace App\Console\Command\Locale;

use App\Console\Command\CommandAbstract;
use App\Environment;
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
class GenerateCommand extends CommandAbstract
{
    public function __construct(
        protected Environment $environment
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generate Locales');

        $exportDir = $this->environment->getBaseDirectory() . '/resources/locale';

        $dest_file = $exportDir . '/default.pot';

        $translations = new Translations();

        // Find all JS/Vue file translations.
        $directory = new RecursiveDirectoryIterator($this->environment->getBaseDirectory() . '/frontend/vue');
        $iterator = new RecursiveIteratorIterator($directory);

        $vueRegex = new RegexIterator($iterator, '/^.+\.(vue)$/i', RegexIterator::GET_MATCH);
        foreach ($vueRegex as $pathMatch) {
            $translations->addFromVueJsFile($pathMatch[0]);
        }

        $jsRegex = new RegexIterator($iterator, '/^.+\.(js)$/i', RegexIterator::GET_MATCH);
        foreach ($jsRegex as $pathMatch) {
            $translations->addFromJsCodeFile($pathMatch[0]);
        }

        // Find all PHP/PHTML files in the application's code.
        $translatable_folders = [
            $this->environment->getBaseDirectory() . '/src',
            $this->environment->getBaseDirectory() . '/config',
            $this->environment->getViewsDirectory(),
        ];

        foreach ($translatable_folders as $folder) {
            $directory = new RecursiveDirectoryIterator($folder);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.(phtml|php)$/i', RegexIterator::GET_MATCH);

            foreach ($regex as $path_match) {
                $path = $path_match[0];
                $translations->addFromPhpCodeFile($path);
            }
        }

        $translations->ksort();

        $translations->toPoFile($dest_file);

        $io->success('Locales generated.');
        return 0;
    }
}
