<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Cache\DatabaseCache;
use App\Container\SettingsAwareTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:cache:clear',
    description: 'Clear all application caches.',
    aliases: ['cache:clear']
)]
final class ClearCacheCommand extends CommandAbstract
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly AdapterInterface $cache,
        private readonly DatabaseCache $dbCache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Flush all Redis entries.
        $this->cache->clear();
        $this->dbCache->clear();

        // Clear cached system settings.
        $settings = $this->readSettings();
        $settings->updateUpdateLastRun();
        $settings->setUpdateResults(null);

        if ('127.0.0.1' !== $settings->getExternalIp()) {
            $settings->setExternalIp(null);
        }

        $this->writeSettings($settings);

        $io->success('Local cache flushed.');
        return 0;
    }
}
