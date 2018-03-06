<?php
namespace AzuraCast\Radio;
use App\Exception\NotFound;
use Entity\Station;
use Slim\Container;

/**
 * Manager class for radio adapters.
 */
class Adapters
{
    protected $di;

    public function __construct(Container $di)
    {
        $this->di = $di;
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

        /** @var Frontend\FrontendAbstract $adapter */
        $adapter = $this->di[$class_name];
        $adapter->setStation($station);
        return $adapter;
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

        /** @var Backend\BackendAbstract $adapter */
        $adapter = $this->di[$class_name];
        $adapter->setStation($station);
        return $adapter;
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
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'Icecast 2.4'),
                    'class' => Frontend\Icecast::class,
                ],
                'shoutcast2' => [
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'SHOUTcast DNAS 2'),
                    'class' => Frontend\SHOUTcast::class,
                ],
                'remote' => [
                    'name' => _('Connect to a <b>remote radio server</b>'),
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
                    'name' => sprintf(_('Use <b>%s</b> on this server'), 'Liquidsoap'),
                    'class' => Backend\Liquidsoap::class,
                ],
                'none' => [
                    'name' => _('<b>Do not use</b> an AutoDJ service'),
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