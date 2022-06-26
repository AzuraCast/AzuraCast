<?php

declare(strict_types=1);

namespace App\Nginx;

use App\Entity\Station;
use App\Event\Nginx\WriteNginxConfiguration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Supervisor\SupervisorInterface;
use Symfony\Component\Filesystem\Filesystem;

final class Nginx
{
    private const PROCESS_NAME = 'nginx';

    public function __construct(
        private readonly SupervisorInterface $supervisor,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function writeConfiguration(
        Station $station,
        bool $reloadIfChanged = true,
    ): void {
        $configPath = $this->getConfigPath($station);

        $currentConfig = (is_file($configPath))
            ? file_get_contents($configPath)
            : null;

        $newConfig = $this->getConfiguration($station, true);

        if ($currentConfig === $newConfig) {
            return;
        }

        (new Filesystem())->dumpFile($configPath, $newConfig);

        if ($reloadIfChanged) {
            $this->reload();
        }
    }

    public function getConfiguration(
        Station $station,
        bool $writeToDisk = false
    ): string {
        $event = new WriteNginxConfiguration(
            $station,
            $writeToDisk
        );

        $this->eventDispatcher->dispatch($event);

        return $event->buildConfiguration();
    }

    public function reload(): void
    {
        $this->supervisor->signalProcess(self::PROCESS_NAME, 'HUP');
    }

    public function reopenLogs(): void
    {
        $this->supervisor->signalProcess(self::PROCESS_NAME, 'USR1');
    }

    private function getConfigPath(Station $station): string
    {
        return $station->getRadioConfigDir() . '/nginx.conf';
    }
}
