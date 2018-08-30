<?php
namespace App\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocaleGenerate extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('locale:generate')
            ->setDescription('Generate the translation locale file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dest_file = APP_INCLUDE_ROOT.'/resources/locale/default.pot';
        $translations = new \Gettext\Translations;

        // Find all PHP/PHTML files in the application's code.
        $translatable_folders = [
            APP_INCLUDE_ROOT.'/src',
            APP_INCLUDE_ROOT.'/config',
            APP_INCLUDE_ROOT.'/resources/templates',
        ];

        foreach($translatable_folders as $folder) {
            $directory = new \RecursiveDirectoryIterator($folder);
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator($iterator, '/^.+\.(phtml|php)$/i', \RecursiveRegexIterator::GET_MATCH);

            foreach($regex as $path_match) {
                $path = $path_match[0];
                $translations->addFromPhpCodeFile($path);
            }
        }

        $translations->toPoFile($dest_file);

        $output->writeln('Locales generated.');
        return 0;
    }
}
