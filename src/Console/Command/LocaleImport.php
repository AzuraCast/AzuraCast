<?php
namespace App\Console\Command;

use Azura\Console\Command\CommandAbstract;
use Azura\Settings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocaleImport extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('locale:import')
            ->setDescription('Convert translated locale files into PHP arrays.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app_settings = $this->get('settings');
        $locales = $app_settings['locale']['supported'];

        $locale_base = $app_settings[Settings::BASE_DIR].'/resources/locale';

        foreach($locales as $locale_key => $locale_name) {

            $locale_source = $locale_base.'/'.$locale_key.'/LC_MESSAGES/default.po';

            if (file_exists($locale_source)) {
                /** @var \Gettext\Translations $translations */
                $translations = \Gettext\Translations::fromPoFile($locale_source);

                $locale_dest = $locale_base.'/compiled/'.$locale_key.'.php';
                $translations->toPhpArrayFile($locale_dest);

                $output->writeln('Imported locale: '.$locale_key.' ('.$locale_name.')');
            }
        }

        $output->writeln('Locales imported.');
        return 0;
    }
}
