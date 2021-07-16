<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCacheCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        AdapterInterface $cache,
        EntityManagerInterface $em,
        SettingsRepository $settingsRepo,
    ): int {
        // Flush all Redis entries.
        $cache->clear();

        // Clear "Now Playing" cache on all station rows.
        $em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\Station s SET s.nowplaying=null
            DQL
        )->execute();

        // Clear cached system settings.
        $settings = $settingsRepo->readSettings();
        $settings->setNowplaying(null);
        $settings->updateUpdateLastRun();
        $settings->setUpdateResults(null);

        if ('127.0.0.1' !== $settings->getExternalIp()) {
            $settings->setExternalIp(null);
        }

        $settingsRepo->writeSettings($settings);

        $io->success('Local cache flushed.');
        return 0;
    }
}
