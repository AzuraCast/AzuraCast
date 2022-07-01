<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(
        private readonly AdapterInterface $cache,
        private readonly EntityManagerInterface $em,
        private readonly SettingsRepository $settingsRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Flush all Redis entries.
        $this->cache->clear();

        // Clear "Now Playing" cache on all station rows.
        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\Station s SET s.nowplaying=null
            DQL
        )->execute();

        // Clear cached system settings.
        $settings = $this->settingsRepo->readSettings();
        $settings->updateUpdateLastRun();
        $settings->setUpdateResults(null);

        if ('127.0.0.1' !== $settings->getExternalIp()) {
            $settings->setExternalIp(null);
        }

        $this->settingsRepo->writeSettings($settings);

        $io->success('Local cache flushed.');
        return 0;
    }
}
