<?php
namespace App\Console\Command;

use App\Entity;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetSetting extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:settings:set')
            ->setDescription('Set the value of a setting in the AzuraCast settings database.')
            ->addArgument(
                'setting_key',
                InputArgument::REQUIRED,
                'The name of the setting to set.'
            )
            ->addArgument(
                'setting_value',
                InputArgument::REQUIRED,
                'The JSON-encoded (or plain, if an int or string) value.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('AzuraCast Settings');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        $setting_key = $input->getArgument('setting_key');
        $setting_value = $input->getArgument('setting_value');

        if (strtolower($setting_value) === 'null') {
            $settings_repo->deleteSetting($setting_key);

            $io->success(sprintf('Setting "%s" removed.', $setting_key));
            return null;
        }

        if (0 === strpos($setting_value, '{')) {
            $setting_value = json_decode($setting_value, true);
        }

        $settings_repo->setSetting($setting_key, $setting_value);

        $io->success(sprintf('Setting "%s" updated.', $setting_key));
    }
}
