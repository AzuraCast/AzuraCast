<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Exception\SupervisorException;
use App\Service\ServiceControl\ServiceData;
use InvalidArgumentException;
use Supervisor\Exception\Fault\BadNameException;
use Supervisor\Exception\Fault\NotRunningException;
use Supervisor\Exception\SupervisorException as SupervisorLibException;
use Supervisor\ProcessStates;
use Supervisor\SupervisorInterface;

final class ServiceControl
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly SupervisorInterface $supervisor,
        private readonly Centrifugo $centrifugo
    ) {
    }

    /** @return ServiceData[] */
    public function getServices(): array
    {
        $services = [];

        foreach ($this->getServiceNames() as $name => $description) {
            try {
                $isRunning = in_array(
                    $this->supervisor->getProcess($name)->getState(),
                    [
                        ProcessStates::Running,
                        ProcessStates::Starting,
                    ],
                    true
                );
            } catch (BadNameException) {
                $isRunning = false;
            }

            $services[] = new ServiceData(
                $name,
                $description,
                $isRunning
            );
        }

        return $services;
    }

    public function restart(string $service): void
    {
        $serviceNames = $this->getServiceNames();
        if (!isset($serviceNames[$service])) {
            throw new InvalidArgumentException(
                sprintf('Service "%s" is not managed by AzuraCast.', $service)
            );
        }

        try {
            $this->supervisor->stopProcess($service);
        } catch (NotRunningException) {
        }

        try {
            $this->supervisor->startProcess($service);
        } catch (SupervisorLibException $e) {
            throw SupervisorException::fromSupervisorLibException($e, $service);
        }
    }

    public function getServiceNames(): array
    {
        $services = [
            'cron' => __('Runs routine synchronized tasks'),
            'mariadb' => __('Database'),
            'nginx' => __('Web server'),
            'roadrunner' => __('Roadrunner PHP Server'),
            'php-fpm' => __('PHP FastCGI Process Manager'),
            'php-nowplaying' => __('Now Playing manager service'),
            'php-worker' => __('PHP queue processing worker'),
            'redis' => __('Cache'),
            'sftpgo' => __('SFTP service'),
            'centrifugo' => __('Live Now Playing updates'),
            'vite' => __('Frontend Assets'),
        ];

        if (!$this->centrifugo->isSupported()) {
            unset($services['centrifugo']);
        }

        if (!$this->environment->useLocalDatabase()) {
            unset($services['mariadb']);
        }

        if (!$this->environment->useLocalRedis()) {
            unset($services['redis']);
        }

        if (!$this->environment->isDevelopment()) {
            unset($services['php-fpm']);
            unset($services['vite']);
        }

        return $services;
    }
}
