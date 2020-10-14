<?php

namespace App\Console\Command\Settings;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Utilities;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Entity\Repository\SettingsRepository $settings_repo
    ): int {
        $io->title(__('AzuraCast Settings'));

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

        return 0;
    }
}
