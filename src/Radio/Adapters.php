<?php
namespace App\Radio;
use App\Exception\NotFound;
use App\Entity\Station;
use Pimple\Psr11\ServiceLocator;
use Slim\Container;

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

    public const DEFAULT_FRONTEND = self::FRONTEND_ICECAST;
    public const DEFAULT_BACKEND = self::BACKEND_LIQUIDSOAP;

    /** @var ServiceLocator */
    protected $adapters;

    public function __construct(ServiceLocator $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @param Station $station
     * @return Frontend\FrontendAbstract
     * @throws NotFound
     */
    public function getFrontendAdapter(Station $station): Frontend\FrontendAbstract
    {
        $adapters = self::getFrontendAdapters();

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
     * @param Station $station
     * @return Backend\BackendAbstract
     * @throws NotFound
     */
    public function getBackendAdapter(Station $station): Backend\BackendAbstract
    {
        $adapters = self::getBackendAdapters();

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
     * @return array
     */
    public static function getFrontendAdapters(): array
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
    public static function getBackendAdapters(): array
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
}
