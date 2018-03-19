<?php
namespace App\Console\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocaleGenerate extends \App\Console\Command\CommandAbstract
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
        $dest_file = APP_INCLUDE_BASE.'/locale/default.pot';
        $translations = new \Gettext\Translations;

        // Find all PHP/PHTML files in the application's code.
        $directory = new \RecursiveDirectoryIterator(APP_INCLUDE_BASE);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.(phtml|php)$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach($regex as $path_match) {
            $path = $path_match[0];
            $translations->addFromPhpCodeFile($path);
        }

        $translations->toPoFile($dest_file);

        $output->writeln('Locales generated.');
    }
}