<?php
namespace App\Console\Command;

use App\Entity;
use App\Utilities;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListSettings extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:settings:list')
            ->setDescription(__('List all settings in the AzuraCast settings database.'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(__('AzuraCast Settings'));

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        $headers = [
            __('Setting Key'),
            __('Setting Value'),
        ];
        $rows = [];

        $all_settings = $settings_repo->fetchAll();
        foreach ($all_settings as $setting_key => $setting_value) {
            $value = print_r($setting_value, true);
            $value = Utilities::truncateText($value, 600);

            $rows[] = [$setting_key, $value];
        }

        $io->table($headers, $rows);
    }
}
