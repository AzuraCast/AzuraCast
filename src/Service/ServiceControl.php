<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\SupervisorException;
use App\Service\ServiceControl\ServiceData;
use Supervisor\Exception\Fault\BadNameException;
use Supervisor\Exception\Fault\NotRunningException;
use Supervisor\Exception\SupervisorException as SupervisorLibException;
use Supervisor\ProcessStates;
use Supervisor\SupervisorInterface;

final class ServiceControl
{
    public function __construct(
        private readonly SupervisorInterface $supervisor,
        private readonly Environment $environment,
        private readonly Centrifugo $centrifugo
    ) {
    }

    /** @return ServiceData[] */
    public function getServices(): array
    {
        $services = [];

        foreach (self::getServiceNames() as $name => $description) {
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
        $serviceNames = self::getServiceNames();
        if (!isset($serviceNames[$service])) {
            throw new \InvalidArgumentException(
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

    public static function getServiceNames(): array
    {
        return [
            'beanstalkd' => __('Message queue delivery service'),
            'cron' => __('Runs routine synchronized tasks'),
            'mariadb' => __('Database'),
            'nginx' => __('Web server'),
            'php-fpm' => __('PHP FastCGI Process Manager'),
            'php-nowplaying' => __('Now Playing manager service'),
            'php-worker' => __('PHP queue processing worker'),
            'redis' => __('Cache'),
            'sftpgo' => __('SFTP service'),
            'centrifugo' => __('Live Now Playing updates'),
        ];

        if (!$this->centrifugo->isSupported()) {
            unset($services['centrifugo']);
        }

        if (!$this->environment->useLocalDatabase()) {
            unset($services['mariadb']);
        }

        return $services;
    }
}
