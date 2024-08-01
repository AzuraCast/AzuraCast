<?php

declare(strict_types=1);

namespace App\Console\Command\Settings;

use App\Console\Command\CommandAbstract;
use App\Container\SettingsAwareTrait;
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
    use SettingsAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(__('AzuraCast Settings'));

        $headers = [
            __('Setting Key'),
            __('Setting Value'),
        ];
        $rows = [];

        $settings = $this->readSettings();
        foreach ($this->settingsRepo->toArray($settings) as $settingKey => $settingValue) {
            $value = print_r($settingValue, true);
            $value = Utilities\Strings::truncateText($value, 600);

            $rows[] = [$settingKey, $value];
        }

        $io->table($headers, $rows);

        return 0;
    }
}
