<?php
namespace App\Service;

use App\Message;
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
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message)
    {
        if (!$message instanceof Message\NotifyNChanMessage || !self::isSupported()) {
            return;
        }

        $this->http_client->post('http://localhost:9010/pub/'.urlencode($message->station_id), [
            'json' => $message->nowplaying,
        ]);
    }

    /**
     * @return bool Whether NChan is expected to be running on this installation.
     */
    public static function isSupported(): bool
    {
        return (APP_INSIDE_DOCKER
            && APP_DOCKER_REVISION >= 5
            && !APP_TESTING_MODE);
    }
}
