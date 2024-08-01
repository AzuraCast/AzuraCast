<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Service\AzuraCastCentral;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:ip',
    description: 'Get the external IP address for this instance.',
)]
final class GetIpCommand extends CommandAbstract
{
    public function __construct(
        private readonly AzuraCastCentral $acCentral,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->write($this->acCentral->getIp() ?? 'Unknown');
        return 0;
    }
}
