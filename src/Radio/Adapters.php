<?php

declare(strict_types=1);

namespace App\Radio;

use App\Entity;
use App\Exception\NotFoundException;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;
use Psr\Container\ContainerInterface;

/**
 * Manager class for radio adapters.
 */
final class Adapters
{
    public function __construct(
        private readonly ContainerInterface $adapters
    ) {
    }

    public function getFrontendAdapter(Entity\Station $station): ?Frontend\AbstractFrontend
    {
        $className = $station->getFrontendTypeEnum()->getClass();

        return (null !== $className && $this->adapters->has($className))
            ? $this->adapters->get($className)
            : null;
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listFrontendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(FrontendAdapters::cases(), $checkInstalled);
    }

    public function getBackendAdapter(Entity\Station $station): ?Liquidsoap
    {
        $className = $station->getBackendTypeEnum()->getClass();

        return (null !== $className && $this->adapters->has($className))
            ? $this->adapters->get($className)
            : null;
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listBackendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(BackendAdapters::cases(), $checkInstalled);
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
    private function listAdaptersFromEnum(array $cases, bool $checkInstalled = false): array
    {
        $adapters = [];
        foreach ($cases as $adapter) {
            $adapters[$adapter->getValue()] = [
                'enum' => $adapter,
                'name' => $adapter->getName(),
                'class' => $adapter->getClass(),
            ];
        }

        if ($checkInstalled) {
            return array_filter(
                $adapters,
                function ($adapter_info) {
                    if (null === $adapter_info['class']) {
                        return true;
                    }

                    /** @var AbstractLocalAdapter $adapter */
                    $adapter = $this->adapters->get($adapter_info['class']);
                    return $adapter->isInstalled();
                }
            );
        }

        return $adapters;
    }
}
