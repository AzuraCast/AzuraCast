<?php
namespace App\Service;

use App\Message;
use App\Utilities;
use GuzzleHttp\Client;

/**
 * Utility class for managing NChan, the nginx websocket/SSE/long-polling module.
 */
class NChan
{
    /** @var Client */
    protected $http_client;

    /**
     * @param Client $http_client
     */
    public function __construct(Client $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * @return bool Whether NChan is expected to be running on this installation.
     */
    public static function isSupported(): bool
    {
        if (APP_TESTING_MODE) {
            return false;
        }

        if (APP_INSIDE_DOCKER) {
            return APP_DOCKER_REVISION >= 5;
        }

        $os_details = Utilities::getOperatingSystemDetails();
        return 'bionic' === $os_details['VERSION_CODENAME'];
    }
}
