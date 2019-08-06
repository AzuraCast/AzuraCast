<?php
/**
 * PHP-DI Services
 */

use Azura\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

return [

    /*
     * Slim Component Overrides
     */

    // URL Router helper
    App\Http\Router::class => function(
        Settings $settings,
        \Slim\App $app,
        EntityManager $em
    ) {
        $route_parser = $app->getRouteCollector()->getRouteParser();
        return new App\Http\Router($settings, $route_parser, $em);
    },
    Azura\Http\RouterInterface::class => DI\Get(App\Http\Router::class),

    // Error Handler
    App\Http\ErrorHandler::class => DI\autowire(),
    Slim\Interfaces\ErrorHandlerInterface::class => DI\Get(App\Http\ErrorHandler::class),

    /*
     * Doctrine Database
     */

    EntityManager::class => DI\decorate(function(EntityManager $em, ContainerInterface $di) {
        $event_manager = $em->getEventManager();
        $event_manager->addEventSubscriber(new App\Doctrine\Event\StationRequiresRestart);
        return $em;
    }),

    /*
     * View
     */

    Azura\View::class => DI\decorate(function(Azura\View $view, ContainerInterface $di) {
        $view->registerFunction('mailto', function ($address, $link_text = null) {
            $address = substr(chunk_split(bin2hex(" $address"), 2, ";&#x"), 3, -3);
            $link_text = $link_text ?? $address;
            return '<a href="mailto:' . $address . '">' . $link_text . '</a>';
        });
        $view->registerFunction('pluralize', function ($word, $num = 0) {
            if ((int)$num === 1) {
                return $word;
            }
            return Doctrine\Common\Inflector\Inflector::pluralize($word);
        });
        $view->registerFunction('truncate', function ($text, $length = 80) {
            return App\Utilities::truncateText($text, $length);
        });
        $view->registerFunction('truncateUrl', function($url) {
            return App\Utilities::truncateUrl($url);
        });
        $view->registerFunction('link', function($url, $external = true, $truncate = true) {
            $url = htmlspecialchars($url, \ENT_QUOTES, 'UTF-8');

            $a = ['href="'.$url.'"'];
            if ($external) {
                $a[] = 'target="_blank"';
            }

            $a_body = ($truncate) ? App\Utilities::truncateUrl($url) : $url;
            return '<a '.implode(' ', $a).'>'.$a_body.'</a>';
        });

        $view->addData([
            'assets' => $di->get(Azura\Assets::class),
            'auth' => $di->get(App\Auth::class),
            'acl' => $di->get(App\Acl::class),
            'customization' => $di->get(App\Customization::class),
            'version' => $di->get(App\Version::class),
        ]);
        return $view;
    }),

    /*
     * Event Dispatcher
     */

    Azura\EventDispatcher::class => DI\decorate(function(Azura\EventDispatcher $dispatcher, ContainerInterface $di) {
        if ($di->has(App\Plugins::class)) {
            /** @var App\Plugins $plugins */
            $plugins = $di->get(App\Plugins::class);

            // Register plugin-provided events.
            $plugins->registerEvents($dispatcher);
        }

        return $dispatcher;
    }),

    /*
     * Console
     */

    Azura\Console\Application::class => DI\decorate(function(Azura\Console\Application $console, ContainerInterface $di) {
        /** @var App\Version $version */
        $version = $di->get(App\Version::class);

        /** @var Settings $settings */
        $settings = $di->get(Settings::class);

        $console->setName($settings[Settings::APP_NAME].' Command Line Tools ('.$settings[Settings::APP_ENV].')');
        $console->setVersion($version->getVersion());

        return $console;
    }),

    /*
     * AzuraCast-specific dependencies
     */

    App\Acl::class => DI\autowire(),
    App\Auth::class => DI\autowire(),
    App\ApiUtilities::class => DI\autowire(),
    App\Customization::class => DI\autowire(),
    App\Version::class => DI\autowire(),
    App\Service\Sentry::class => DI\autowire(),
    App\Service\NChan::class => DI\autowire(),
    App\Validator\Constraints\StationPortCheckerValidator::class => DI\autowire(),

    // Message queue manager class
    App\MessageQueue::class => function(
        DI\FactoryInterface $factory,
        ContainerInterface $di,
        Monolog\Logger $logger
    ) {
        // Build QueueFactory
        /** @var Redis $redis */
        $redis = $factory->make(Redis::class);

        $redis->select(4);
        $driver = new Bernard\Driver\PhpRedis\Driver($redis);

        $normalizer = new Normalt\Normalizer\AggregateNormalizer([
            new Bernard\Normalizer\EnvelopeNormalizer,
            new Symfony\Component\Serializer\Normalizer\PropertyNormalizer
        ]);

        $symfony_serializer = new Symfony\Component\Serializer\Serializer([$normalizer]);
        $serializer = new Bernard\Serializer($normalizer);

        $queue_factory = new Bernard\QueueFactory\PersistentFactory($driver, $serializer);

        // Event dispatcher
        $dispatcher = new Symfony\Component\EventDispatcher\EventDispatcher;

        // Build Producer
        $producer = new Bernard\Producer($queue_factory, $dispatcher);

        // Build Consumer
        $receivers = require __DIR__ . '/messagequeue.php';
        $router = new Bernard\Router\ReceiverMapRouter($receivers, new Bernard\Router\ContainerReceiverResolver($di));

        $consumer = new Bernard\Consumer($router, $dispatcher);

        $mq = new App\MessageQueue(
            $queue_factory,
            $producer,
            $consumer,
            $logger
        );

        $dispatcher->addSubscriber($mq);
        return $mq;
    },

    // MaxMind (IP Geolocation database for listener metadata)
    MaxMind\Db\Reader::class => function(Settings $settings) {
        $mmdb_path = dirname($settings[Settings::BASE_DIR]).'/geoip/GeoLite2-City.mmdb';
        return new MaxMind\Db\Reader($mmdb_path);
    },

    // InfluxDB
    InfluxDB\Database::class => function(Settings $settings) {
        $opts = [
            'host' => $settings->isDocker() ? 'influxdb' : 'localhost',
            'port' => 8086,
        ];

        $influx = new InfluxDB\Client($opts['host'], $opts['port']);
        return $influx->selectDB('stations');
    },

    // Supervisor manager
    Supervisor\Supervisor::class => function(Settings $settings) {
        $guzzle_client = new GuzzleHttp\Client();
        $client = new fXmlRpc\Client(
            'http://' . ($settings->isDocker() ? 'stations' : '127.0.0.1') . ':9001/RPC2',
            new fXmlRpc\Transport\HttpAdapterTransport(
                new Http\Message\MessageFactory\GuzzleMessageFactory(),
                new Http\Adapter\Guzzle6\Client($guzzle_client)
            )
        );

        $connector = new Supervisor\Connector\XmlRpc($client);
        $supervisor = new Supervisor\Supervisor($connector);

        if (!$supervisor->isConnected()) {
            throw new \Azura\Exception(sprintf('Could not connect to supervisord.'));
        }

        return $supervisor;
    },

    Azura\Assets::class => function(Azura\Config $config, Settings $settings) {
        $libraries = $config->get('assets');

        $versioned_files = [];
        $assets_file = $settings[Settings::BASE_DIR].'/web/static/assets.json';
        if (file_exists($assets_file)) {
            $versioned_files = json_decode(file_get_contents($assets_file), true);
        }

        return new Azura\Assets($libraries, $versioned_files);
    },

    /*
     * Radio Components
     */

    App\Radio\Adapters::class => DI\autowire(),
    App\Radio\AutoDJ::class => DI\autowire(),
    App\Radio\Configuration::class => DI\autowire(),

    App\Radio\Filesystem::class => function(DI\FactoryInterface $factory) {
        /** @var Redis $redis */
        $redis = $factory->make(Redis::class);
        $redis->select(5);

        return new App\Radio\Filesystem($redis);
    },

    App\Radio\Backend\Liquidsoap::class => DI\autowire(),
    App\Radio\Backend\None::class => DI\autowire(),

    App\Radio\Frontend\Icecast::class => DI\autowire(),
    App\Radio\Frontend\Remote::class => DI\autowire(),
    App\Radio\Frontend\SHOUTcast::class => DI\autowire(),

    App\Radio\Remote\AzuraRelay::class => DI\autowire(),
    App\Radio\Remote\Icecast::class => DI\autowire(),
    App\Radio\Remote\SHOUTcast1::class => DI\autowire(),
    App\Radio\Remote\SHOUTcast2::class => DI\autowire(),

    /*
     * Synchronized (Cron) Tasks
     */

    App\Sync\Runner::class => function(
        ContainerInterface $di,
        EntityManager $em,
        Monolog\Logger $logger
    ) {
        /** @var App\Entity\Repository\SettingsRepository $settingsRepo */
        $settingsRepo = $em->getRepository(App\Entity\Settings::class);

        return new App\Sync\Runner(
            $settingsRepo,
            $logger,
            [
                $di->get(App\Sync\Task\NowPlaying::class),
                $di->get(App\Sync\Task\ReactivateStreamer::class),
            ],
            [ // Every minute tasks
                $di->get(App\Sync\Task\RadioRequests::class),
                $di->get(App\Sync\Task\Backup::class),
                $di->get(App\Sync\Task\RelayCleanup::class),
            ],
            [ // Every 5 minutes tasks
                $di->get(App\Sync\Task\Media::class),
                $di->get(App\Sync\Task\CheckForUpdates::class),
            ],
            [ // Every hour tasks
                $di->get(App\Sync\Task\Analytics::class),
                $di->get(App\Sync\Task\RadioAutomation::class),
                $di->get(App\Sync\Task\HistoryCleanup::class),
                $di->get(App\Sync\Task\RotateLogs::class),
            ]
        );
    },

    App\Sync\Task\Analytics::class => DI\autowire(),
    App\Sync\Task\Backup::class => DI\autowire(),
    App\Sync\Task\CheckForUpdates::class => DI\autowire(),
    App\Sync\Task\HistoryCleanup::class => DI\autowire(),
    App\Sync\Task\Media::class => DI\autowire(),
    App\Sync\Task\ReactivateStreamer::class => DI\autowire(),
    App\Sync\Task\NowPlaying::class => DI\autowire(),
    App\Sync\Task\RadioAutomation::class => DI\autowire(),
    App\Sync\Task\RadioRequests::class => DI\autowire(),
    App\Sync\Task\RelayCleanup::class => DI\autowire(),
    App\Sync\Task\RotateLogs::class => DI\autowire(),

    /**
     * Web Hooks
     */

    App\Webhook\Dispatcher::class => function(
        ContainerInterface $di,
        Azura\Config $config,
        Monolog\Logger $logger
    ){
        $webhooks = $config->get('webhooks');
        $services = [];
        foreach($webhooks['webhooks'] as $webhook_key => $webhook_info) {
            $services[$webhook_key] = $di->get($webhook_info['class']);
        }

        return new App\Webhook\Dispatcher($logger, $services);
    },

    App\Webhook\Connector\Discord::class => DI\autowire(),
    App\Webhook\Connector\Generic::class => DI\autowire(),
    App\Webhook\Connector\Local::class => DI\autowire(),
    App\Webhook\Connector\TuneIn::class => DI\autowire(),
    App\Webhook\Connector\Telegram::class => DI\autowire(),
    App\Webhook\Connector\Twitter::class => DI\autowire(),

    /*
     * Middleware
     */

    App\Middleware\EnforceSecurity::class => DI\autowire(),
    App\Middleware\GetCurrentUser::class => DI\autowire(),
    App\Middleware\GetStation::class => DI\autowire(),
    App\Middleware\Permissions::class => DI\create(),
    App\Middleware\InjectAcl::class => DI\autowire(),
    App\Middleware\RequireStation::class => DI\create(),
    App\Middleware\RequireLogin::class => DI\create(),

    // Module-specific middleware
    App\Middleware\Module\Admin::class => DI\autowire(),
    App\Middleware\Module\Api::class => DI\autowire(),
    App\Middleware\Module\Stations::class => DI\autowire(),
    App\Middleware\Module\StationFiles::class => DI\autowire(),

    /*
     * Notifications
     */

    App\Notification\Manager::class => DI\autowire(),

    /*
     * Forms
     */

    App\Form\EntityFormManager::class => function(
        EntityManager $em,
        Symfony\Component\Serializer\Serializer $serializer,
        Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        ContainerInterface $di
    ) {
        $custom_forms = [
            App\Entity\Station::class           => $di->get(App\Form\StationForm::class),
            App\Entity\User::class              => $di->get(App\Form\UserForm::class),
            App\Entity\RolePermission::class    => $di->get(App\Form\PermissionsForm::class),
            App\Entity\StationPlaylist::class   => $di->get(App\Form\StationPlaylistForm::class),
            App\Entity\StationMount::class      => $di->get(App\Form\StationMountForm::class),
            App\Entity\StationWebhook::class    => $di->get(App\Form\StationWebhookForm::class),
        ];

        return new App\Form\EntityFormManager($em, $serializer, $validator, $custom_forms);
    },

    App\Form\PermissionsForm::class => DI\autowire(),
    App\Form\StationForm::class => DI\autowire(),
    App\Form\StationCloneForm::class => DI\autowire(),
    App\Form\StationMountForm::class => DI\autowire(),
    App\Form\StationPlaylistForm::class => DI\autowire(),
    App\Form\StationWebhookForm::class => DI\autowire(),
    App\Form\UserForm::class => DI\autowire(),

    /*
     * Controller Groups
     */

    'App\Controller\Admin\*Controller' => DI\autowire(),

    'App\Controller\Api\*Controller' => DI\autowire(),
    'App\Controller\Api\Admin\*Controller' => DI\autowire(),
    'App\Controller\Api\Stations\*Controller' => DI\autowire(),

    'App\Controller\Frontend\*Controller' => DI\autowire(),

    'App\Controller\Stations\*Controller' => DI\autowire(),
    'App\Controller\Stations\Files\*Controller' => DI\autowire(),
    'App\Controller\Stations\Reports\*Controller' => DI\autowire(),
];
