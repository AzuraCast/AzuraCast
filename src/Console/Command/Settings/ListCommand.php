<?php

declare(strict_types=1);

namespace App\Console\Command\Settings;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Utilities;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:settings:list',
    description: 'List all settings in the AzuraCast settings database.',
)]
final class ListCommand extends CommandAbstract
{
    public function __construct(
        private readonly Entity\Repository\SettingsRepository $settingsTableRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(__('AzuraCast Settings'));

        $headers = [
            __('Setting Key'),
            __('Setting Value'),
        ];
        $rows = [];

        $settings = $this->settingsTableRepo->readSettings();
        foreach ($this->settingsTableRepo->toArray($settings) as $setting_key => $setting_value) {
            $value = print_r($setting_value, true);
            $value = Utilities\Strings::truncateText($value, 600);

            $rows[] = [$setting_key, $value];
        }

        $io->table($headers, $rows);

        return 0;
    }
}
