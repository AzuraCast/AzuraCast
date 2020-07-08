<?php
namespace App\Console\Command;

use App;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class UptimeWaitCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        App\Service\UptimeWait $uptimeWait
    ) {
        $io->writeln('Waiting for dependent services to go online...');
        $io->progressStart(3);

        try {
            $uptimeWait->waitForDatabase();
            $io->progressAdvance();

            $uptimeWait->waitForInflux();
            $io->progressAdvance();

            $uptimeWait->waitForRedis();
            $io->progressAdvance();
        } catch (Exception $e) {
            $io->error('Error encountered: ' . $e->getMessage() . ' (' . $e->getFile() . ' L' . $e->getLine() . ')');
            return 1;
        }

        $io->progressFinish();
        return 0;
    }
}
