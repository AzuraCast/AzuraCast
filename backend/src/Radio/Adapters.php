<?php

declare(strict_types=1);

namespace App\Radio;

use App\Container\ContainerAwareTrait;
use App\Entity\Station;
use App\Entity\StationRemote;
use App\Exception\NotFoundException;
use App\Exception\StationUnsupportedException;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;

/**
 * Manager class for radio adapters.
 *
 * @phpstan-type AdapterInfo array<string, array{
 *     enum: AdapterTypeInterface,
 *     name: string,
 *     class: class-string|null
 * }>
 */
final class Adapters
{
    use ContainerAwareTrait;

    public function getFrontendAdapter(Station $station): ?Frontend\AbstractFrontend
    {
        $className = $station->frontend_type->getClass();

        return (null !== $className && $this->di->has($className))
            ? $this->di->get($className)
            : null;
    }

    /**
     * @throws StationUnsupportedException
     */
    public function requireFrontendAdapter(Station $station): Frontend\AbstractFrontend
    {
        $frontend = $this->getFrontendAdapter($station);

        if (null === $frontend) {
            throw StationUnsupportedException::generic();
        }

        return $frontend;
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listFrontendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(FrontendAdapters::cases(), $checkInstalled);
    }

    public function getBackendAdapter(Station $station): ?Liquidsoap
    {
        $className = $station->backend_type->getClass();

        return (null !== $className && $this->di->has($className))
            ? $this->di->get($className)
            : null;
    }

    /**
     * @throws StationUnsupportedException
     */
    public function requireBackendAdapter(Station $station): Liquidsoap
    {
        $backend = $this->getBackendAdapter($station);

        if (null === $backend) {
            throw StationUnsupportedException::generic();
        }

        return $backend;
    }

    /**
     * @param bool $checkInstalled
     * @return mixed[]
     */
    public function listBackendAdapters(bool $checkInstalled = false): array
    {
        return $this->listAdaptersFromEnum(BackendAdapters::cases(), $checkInstalled);
    }

    public function getRemoteAdapter(StationRemote $remote): Remote\AbstractRemote
    {
        $className = $remote->type->getClass();
        if ($this->di->has($className)) {
            return $this->di->get($className);
        }

        throw new NotFoundException(
            sprintf('Adapter not found: %s', $className)
        );
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
     * @return AdapterInfo
     */
    private function listAdaptersFromEnum(array $cases, bool $checkInstalled = false): array
    {
        /** @var AdapterInfo $adapters */
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
                function ($adapterInfo) {
                    if (null === $adapterInfo['class']) {
                        return true;
                    }

                    $adapter = $this->di->get($adapterInfo['class']);
                    return ($adapter instanceof AbstractLocalAdapter)
                        ? $adapter->isInstalled()
                        : true;
                }
            );
        }

        return $adapters;
    }
}
