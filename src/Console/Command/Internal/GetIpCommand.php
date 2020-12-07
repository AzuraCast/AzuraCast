<?php

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Service\AzuraCastCentral;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetIpCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        AzuraCastCentral $acCentral
    ): int {
        $io->write($acCentral->getIp());
        return 0;
    }
}
