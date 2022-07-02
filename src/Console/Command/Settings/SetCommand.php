<?php

declare(strict_types=1);

namespace App\Console\Command\Settings;

use App\Console\Command\CommandAbstract;
use App\Entity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:settings:set',
    description: 'Set the value of a setting in the AzuraCast settings database.',
)]
final class SetCommand extends CommandAbstract
{
    public function __construct(
        private readonly Entity\Repository\SettingsRepository $settingsTableRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('setting-key', InputArgument::REQUIRED)
            ->addArgument('setting-value', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $settingKey = $input->getArgument('setting-key');
        $settingValue = $input->getArgument('setting-value');

        $io->title('AzuraCast Settings');

        if (strtolower($settingValue) === 'null') {
            $this->settingsTableRepo->writeSettings([$settingKey => null]);

            $io->success(sprintf('Setting "%s" removed.', $settingKey));
            return 0;
        }

        if (str_starts_with($settingValue, '{')) {
            $settingValue = json_decode($settingValue, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->settingsTableRepo->writeSettings([$settingKey => $settingValue]);

        $io->success(sprintf('Setting "%s" updated.', $settingKey));

        return 0;
    }
}
