<?php
namespace App\Sync\Task;

use App\Version;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use App\Entity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

class CheckForUpdates extends TaskAbstract
{
    const UPDATE_URL = 'https://central.azuracast.com/api/update';
    const UPDATE_THRESHOLD = 3180;

    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Logger */
    protected $logger;

    /** @var Client */
    protected $http_client;

    /** @var Settings */
    protected $app_settings;

    /** @var Version */
    protected $version;

    /**
     * CheckForUpdates constructor.
     * @param Client $http_client
     * @param EntityManager $em
     * @param Logger $logger
     * @param Settings $settings
     * @param Version $version
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(
        Client $http_client,
        EntityManager $em,
        Logger $logger,
        Settings $settings,
        Version $version
    ) {
        $this->em = $em;
        $this->settings_repo = $em->getRepository(Entity\Settings::class);

        $this->logger = $logger;
        $this->http_client = $http_client;
        $this->app_settings = $settings;
        $this->version = $version;
    }

    public function run($force = false)
    {
        if (!$force) {
            $update_last_run = (int)$this->settings_repo->getSetting(Entity\Settings::UPDATE_LAST_RUN, 0);

            if ($update_last_run > (time()-self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        $check_for_updates = (bool)$this->settings_repo->getSetting(Entity\Settings::CENTRAL_UPDATES, 1);

        if (!$check_for_updates || $this->app_settings->isTesting()) {
            $this->logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        $app_uuid = $this->settings_repo->getSetting(Entity\Settings::UNIQUE_IDENTIFIER);

        if (empty($app_uuid)) {
            $app_uuid = Uuid::uuid4()->toString();
            $this->settings_repo->setSetting(Entity\Settings::UNIQUE_IDENTIFIER, $app_uuid);

            $this->logger->debug('Installation did not previously have a unique identifier. New identifier assigned.', ['app_uuid' => $app_uuid]);
        } else {
            $this->logger->debug('Using previously defined installation unique identifier.', ['app_uuid' => $app_uuid]);
        }

        try {
            $response = $this->http_client->request('POST', self::UPDATE_URL, [
                'json' => [
                    'id'        => $app_uuid,
                    'is_docker' => (bool)$this->app_settings[Settings::IS_DOCKER],
                    'version'   => $this->version->getCommitHash(),
                    'environment' => $this->app_settings[Settings::APP_ENV],
                ]
            ]);

            $update_data_raw = $response->getBody()->getContents();

            $this->logger->debug(
                sprintf('AzuraCast Central returned code %d', $response->getStatusCode()),
                ['response_body' => $update_data_raw]
            );
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $update_data = json_decode($update_data_raw, true);

        if (!empty($update_data['updates'])) {
            $this->settings_repo->setSetting(Entity\Settings::UPDATE_RESULTS, $update_data['updates']);
            $this->logger->info('Successfully checked for updates.', ['results' => $update_data]);
        } else {
            $this->logger->error('Error parsing update data response from AzuraCast central.', ['raw' => $update_data_raw]);
        }

        $this->settings_repo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());
    }
}
