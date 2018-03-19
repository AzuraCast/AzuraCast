<?php
namespace AzuraCast\Radio;
use App\Exception\NotFound;
use Entity\Station;
use Pimple\Psr11\ServiceLocator;
use Slim\Container;

/**
 * Manager class for radio adapters.
 */
class Adapters
{
    /** @var ServiceLocator */
    protected $adapters;

    public function __construct(ServiceLocator $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @return Frontend\FrontendAbstract
     * @throws \Exception
     */
    public function getFrontendAdapter(Station $station): Frontend\FrontendAbstract
    {
        $adapters = self::getFrontendAdapters();

        $frontend_type = $station->getFrontendType();

        if (!isset($adapters['adapters'][$frontend_type])) {
            throw new NotFound('Adapter not found: ' . $frontend_type);
        }

        $class_name = $adapters['adapters'][$frontend_type]['class'];

        if ($this->adapters->has($class_name)) {
            /** @var Frontend\FrontendAbstract $adapter */
            $adapter = $this->adapters->get($class_name);
            $adapter->setStation($station);
            return $adapter;
        }

        throw new NotFound('Adapter not found: ' . $class_name);
    }

    /**
     * @return Backend\BackendAbstract
     * @throws \Exception
     */
    public function getBackendAdapter(Station $station): Backend\BackendAbstract
    {
        $adapters = self::getBackendAdapters();

        $backend_type = $station->getBackendType();

        if (!isset($adapters['adapters'][$backend_type])) {
            throw new NotFound('Adapter not found: ' . $backend_type);
        }

        $class_name = $adapters['adapters'][$backend_type]['class'];

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
                'icecast' => [
                    'name' => sprintf(__('Use <b>%s</b> on this server'), 'Icecast 2.4'),
                    'class' => Frontend\Icecast::class,
                ],
                'shoutcast2' => [
                    'name' => sprintf(__('Use <b>%s</b> on this server'), 'SHOUTcast DNAS 2'),
                    'class' => Frontend\SHOUTcast::class,
                ],
                'remote' => [
                    'name' => __('Connect to a <b>remote radio server</b>'),
                    'class' => Frontend\Remote::class,
                ],
            ];

            $adapters = array_filter($adapters, function($adapter_info) {
                /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return [
            'default' => 'icecast',
            'adapters' => $adapters,
        ];
    }

    /**
     * @return array
     */
    public static function getBackendAdapters(): array
    {
        static $adapters;

        if ($adapters === null) {
            $adapters = [
                'liquidsoap' => [
                    'name' => sprintf(__('Use <b>%s</b> on this server'), 'Liquidsoap'),
                    'class' => Backend\Liquidsoap::class,
                ],
                'none' => [
                    'name' => __('<b>Do not use</b> an AutoDJ service'),
                    'class' => Backend\None::class,
                ],
            ];

            $adapters = array_filter($adapters, function ($adapter_info) {
                /** @var \AzuraCast\Radio\AdapterAbstract $adapter_class */
                $adapter_class = $adapter_info['class'];
                return $adapter_class::isInstalled();
            });
        }

        return [
            'default' => 'liquidsoap',
            'adapters' => $adapters,
        ];
    }
}