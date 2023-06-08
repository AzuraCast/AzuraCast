<?php

declare(strict_types=1);

namespace App\Radio;

use App\Container\ContainerAwareTrait;
use App\Entity\Station;
use App\Entity\StationRemote;
use App\Exception\NotFoundException;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\AdapterTypeInterface;
use App\Radio\Enums\BackendAdapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;

/**
 * Manager class for radio adapters.
 */
final class Adapters
{
    use ContainerAwareTrait;

    public function getFrontendAdapter(Station $station): ?Frontend\AbstractFrontend
    {
        $className = $station->getFrontendType()->getClass();

        return (null !== $className && $this->di->has($className))
            ? $this->di->get($className)
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

    public function getBackendAdapter(Station $station): ?Liquidsoap
    {
        $className = $station->getBackendType()->getClass();

        return (null !== $className && $this->di->has($className))
            ? $this->di->get($className)
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

    public function getRemoteAdapter(StationRemote $remote): Remote\AbstractRemote
    {
        $className = $remote->getType()->getClass();
        if ($this->di->has($className)) {
            return $this->di->get($className);
        }

        throw new NotFoundException('Adapter not found: ' . $className);
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
                function ($adapterInfo) {
                    if (null === $adapterInfo['class']) {
                        return true;
                    }

                    /** @var AbstractLocalAdapter $adapter */
                    $adapter = $this->di->get($adapterInfo['class']);
                    return $adapter->isInstalled();
                }
            );
        }

        return $adapters;
    }
}
