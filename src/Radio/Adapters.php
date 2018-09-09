<?php
namespace App\Radio;

use App\Entity;
use App\Exception\NotFound;
use App\Radio\Remote\RemoteAbstract;
use Pimple\Psr11\ServiceLocator;

/**
 * Manager class for radio adapters.
 */
class Adapters
{
    public const FRONTEND_ICECAST = 'icecast';
    public const FRONTEND_SHOUTCAST = 'shoutcast2';
    public const FRONTEND_REMOTE = 'remote';

    public const BACKEND_LIQUIDSOAP = 'liquidsoap';
    public const BACKEND_NONE = 'none';

    public const REMOTE_SHOUTCAST1 = 'shoutcast1';
    public const REMOTE_SHOUTCAST2 = 'shoutcast2';
    public const REMOTE_ICECAST = 'icecast';

    public const DEFAULT_FRONTEND = self::FRONTEND_ICECAST;
    public const DEFAULT_BACKEND = self::BACKEND_LIQUIDSOAP;

    /** @var ServiceLocator */
    protected $adapters;

    public function __construct(ServiceLocator $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @param Entity\Station $station
     * @return Frontend\FrontendAbstract
     * @throws NotFound
     */
    public function getFrontendAdapter(Entity\Station $station): Frontend\FrontendAbstract
    {
        $adapters = self::listFrontendAdapters();

        $frontend_type = $station->getFrontendType();

        if (!isset($adapters[$frontend_type])) {
            throw new NotFound('Adapter not found: ' . $frontend_type);
        }

        $class_name = $adapters[$frontend_type]['class'];

        if ($this->adapters->has($class_name)) {
            /** @var Frontend\FrontendAbstract $adapter */
            $adapter = $this->adapters->get($class_name);
            $adapter->setStation($station);
            return $adapter;
        }

        throw new NotFound('Adapter not found: ' . $class_name);
    }

    /**
     * @param Entity\Station $station
     * @return Backend\BackendAbstract
     * @throws NotFound
     */
    public function getBackendAdapter(Entity\Station $station): Backend\BackendAbstract
    {
        $adapters = self::listBackendAdapters();

        $backend_type = $station->getBackendType();

        if (!isset($adapters[$backend_type])) {
            throw new NotFound('Adapter not found: ' . $backend_type);
        }

        $class_name = $adapters[$backend_type]['class'];

        if ($this->adapters->has($class_name)) {
            /** @var Backend\BackendAbstract $adapter */
            $adapter = $this->adapters->get($class_name);
            $adapter->setStation($station);
            return $adapter;
        }

        throw new NotFound('Adapter not found: ' . $class_name);
    }

    /**
     * @param Entity\Station $station
     * @return Remote\RemoteAbstract[]
     * @throws NotFound
     */
    public function getRemoteAdapters(Entity\Station $station): array
    {
        $remote_adapters = [];

        foreach($station->getRemotes() as $remote) {
            $remote_adapters[] = $this->getRemoteAdapter($station, $remote);
        }

        return $remote_adapters;
    }

    /**
     * Assemble an array of ready-to-operate
     *
     * @param Entity\Station $station
     * @param Entity\StationRemote $remote
     * @return RemoteAbstract
     * @throws NotFound
     */
    public function getRemoteAdapter(Entity\Station $station, Entity\StationRemote $remote): Remote\RemoteAbstract
    {
        $adapters = self::listRemoteAdapters();

        $remote_type = $remote->getType();

        if (!isset($adapters[$remote_type])) {
            throw new NotFound('Adapter not found: ' . $remote_type);
        }

        $class_name = $adapters[$remote_type]['class'];

        if ($this->adapters->has($class_name)) {
            /** @var Remote\RemoteAbstract $adapter */
            $adapter = $this->adapters->get($class_name);

            $adapter->setStation($station);
            $adapter->setRemote($remote);
            return $adapter;
        }

        throw new NotFound('Adapter not found: ' . $class_name);
    }

    /**
     * @return array
     */
    public static function listFrontendAdapters(): array
    {
        static $adapters;

        if ($adapters === null) {
            $adapters = [
                self::FRONTEND_ICECAST => [
                    'name' => __('Use <b>%s</b> on this server', 'Icecast 2.4'),
                    'class' => Frontend\Icecast::class,
                ],
                self::FRONTEND_SHOUTCAST => [
                    'name' => __('Use <b>%s</b> on this server', 'SHOUTcast DNAS 2'),
                    'class' => Frontend\SHOUTcast::class,
                ],
                self::FRONTEND_REMOTE => [
                    'name' => __('Connect to a <b>remote radio server</b>'),
                    'class' => Frontend\Remote::class,
                ],
            ];

            $adapters = array_filter($adapters, function($adapter_info) {
                /** @var \App\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return $adapters;
    }

    /**
     * @return array
     */
    public static function listBackendAdapters(): array
    {
        static $adapters;

        if ($adapters === null) {
            $adapters = [
                self::BACKEND_LIQUIDSOAP => [
                    'name' => __('Use <b>%s</b> on this server', 'Liquidsoap'),
                    'class' => Backend\Liquidsoap::class,
                ],
                self::BACKEND_NONE => [
                    'name' => __('<b>Do not use</b> an AutoDJ service'),
                    'class' => Backend\None::class,
                ],
            ];

            $adapters = array_filter($adapters, function ($adapter_info) {
                /** @var \App\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return $adapters;
    }

    /**
     * @return array
     */
    public static function listRemoteAdapters(): array
    {
        return [
            self::REMOTE_SHOUTCAST1 => [
                'name' => 'SHOUTcast 1',
                'class' => Remote\SHOUTcast1::class,
            ],
            self::REMOTE_SHOUTCAST2 => [
                'name' => 'SHOUTcast 2',
                'class' => Remote\SHOUTcast2::class,
            ],
            self::REMOTE_ICECAST => [
                'name' => 'Icecast',
                'class' => Remote\Icecast::class,
            ],
        ];
    }
}
