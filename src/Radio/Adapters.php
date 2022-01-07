<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Exception\NotFoundException;
use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;
use Psr\Container\ContainerInterface;

/**
 * Manager class for radio adapters.
 */
class Adapters
{
    public function __construct(
        protected ContainerInterface $adapters
    ) {
    }

    /**
     * @param Entity\Station $station
     *
     * @throws NotFoundException
     */
    public function getFrontendAdapter(Entity\Station $station): Frontend\AbstractFrontend
    {
        $class_name = $station->getFrontendTypeEnum()->getClass();
        if ($this->adapters->has($class_name)) {
            return $this->adapters->get($class_name);
        }

        throw new NotFoundException('Adapter not found: ' . $class_name);
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listFrontendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(FrontendAdapters::cases(), $checkInstalled);
    }

    /**
     * @param Entity\Station $station
     *
     * @throws NotFoundException
     */
    public function getBackendAdapter(Entity\Station $station): Backend\AbstractBackend
    {
        $class_name = $station->getBackendTypeEnum()->getClass();
        if ($this->adapters->has($class_name)) {
            return $this->adapters->get($class_name);
        }

        throw new NotFoundException('Adapter not found: ' . $class_name);
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listBackendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(BackendAdapters::cases(), $checkInstalled);
    }

    /**
     * @param Entity\Station $station
     *
     * @return Remote\AdapterProxy[]
     * @throws NotFoundException
     */
    public function getRemoteAdapters(Entity\Station $station): array
    {
        $remote_adapters = [];
        foreach ($station->getRemotes() as $remote) {
            $remote_adapters[] = new Remote\AdapterProxy($this->getRemoteAdapter($station, $remote), $remote);
        }
        return $remote_adapters;
    }

    public function getRemoteAdapter(Entity\Station $station, Entity\StationRemote $remote): Remote\AbstractRemote
    {
        $class_name = $remote->getTypeEnum()->getClass();
        if ($this->adapters->has($class_name)) {
            return $this->adapters->get($class_name);
        }

        throw new NotFoundException('Adapter not found: ' . $class_name);
    }

    /**
     * @return mixed[]
     */
    public function listRemoteAdapters(): array
    {
        return $this->listAdaptersFromEnum(RemoteAdapters::cases());
    }

    /**
     * @param array<AdapterTypeInterface> $cases
     * @param bool $checkInstalled
     * @return mixed[]
     */
    protected function listAdaptersFromEnum(array $cases, bool $checkInstalled = false): array
    {
        $adapters = [];
        foreach ($cases as $adapter) {
            $adapters[$adapter->getValue()] = [
                'enum'  => $adapter,
                'name'  => $adapter->getName(),
                'class' => $adapter->getClass(),
            ];
        }

        if ($checkInstalled) {
            return array_filter(
                $adapters,
                function ($adapter_info) {
                    /** @var AbstractAdapter $adapter */
                    $adapter = $this->adapters->get($adapter_info['class']);
                    return $adapter->isInstalled();
                }
            );
        }

        return $adapters;
    }
}
