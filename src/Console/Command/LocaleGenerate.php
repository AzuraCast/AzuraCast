<?php
namespace App\Console\Command;

use Azura\Console\Command\CommandAbstract;
use Azura\Settings;
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
            ->setDescription(__('Generate the translation locale file.'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = $this->get('settings');

        $dest_file = $settings[Settings::BASE_DIR].'/resources/locale/default.pot';
        $translations = new \Gettext\Translations;

        // Find all PHP/PHTML files in the application's code.
        $translatable_folders = [
            $settings[Settings::BASE_DIR].'/src',
            $settings[Settings::BASE_DIR].'/config',
            $settings[Settings::VIEWS_DIR],
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

        $output->writeln(__('Locales generated.'));
        return 0;
    }
}
