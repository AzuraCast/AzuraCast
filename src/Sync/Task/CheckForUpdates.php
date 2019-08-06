<?php
namespace App\Sync\Task;

use App\Entity;
use App\Version;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class CheckForUpdates extends AbstractTask
{
    const UPDATE_URL = 'https://central.azuracast.com/api/update';
    const UPDATE_THRESHOLD = 3780;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Client */
    protected $http_client;

    /** @var Settings */
    protected $app_settings;

    /** @var Version */
    protected $version;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Client $http_client
     * @param Settings $settings
     * @param Version $version
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        Client $http_client,
        Settings $settings,
        Version $version
    ) {
        parent::__construct($em, $logger);

        $this->settings_repo = $em->getRepository(Entity\Settings::class);

        $this->logger = $logger;
        $this->http_client = $http_client;
        $this->app_settings = $settings;
        $this->version = $version;
    }

    public function run($force = false): void
    {
        if (!$force) {
            $update_last_run = (int)$this->settings_repo->getSetting(Entity\Settings::UPDATE_LAST_RUN, 0);

            if ($update_last_run > (time()-self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        $check_for_updates = (int)$this->settings_repo->getSetting(Entity\Settings::CENTRAL_UPDATES, Entity\Settings::UPDATES_RELEASE_ONLY);

        if (Entity\Settings::UPDATES_NONE === $check_for_updates || $this->app_settings->isTesting()) {
            $this->logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        $app_uuid = $this->settings_repo->getUniqueIdentifier();

        try {
            $request_body = [
                'id'        => $app_uuid,
                'is_docker' => (bool)$this->app_settings[Settings::IS_DOCKER],
                'environment' => $this->app_settings[Settings::APP_ENV],
            ];

            $commit_hash = $this->version->getCommitHash();
            if ($commit_hash) {
                $request_body['version'] = $commit_hash;
            } else {
                $request_body['release'] = Version::FALLBACK_VERSION;
            }

            $response = $this->http_client->request('POST', self::UPDATE_URL, [
                'json' => $request_body,
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
