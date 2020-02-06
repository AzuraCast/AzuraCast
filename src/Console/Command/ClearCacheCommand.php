<?php
namespace App\Console\Command;

use Redis;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCacheCommand extends CommandAbstract
{
    public function __invoke(SymfonyStyle $io, Redis $redis)
    {
        // Flush all Redis entries.
        $redis->flushAll();

        $io->writeln('Local cache flushed.');
        return 0;
    }
}
