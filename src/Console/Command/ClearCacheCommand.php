<?php

namespace App\Console\Command;

use Redis;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCacheCommand extends CommandAbstract
{
    public function __invoke(SymfonyStyle $io, Redis $redis): int
    {
        // Flush all Redis entries.
        $redis->flushAll();

        $io->success('Local cache flushed.');
        return 0;
    }
}
