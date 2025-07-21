<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;
use App\Http\HttpFactory;
use App\Utilities\Types;
use potibm\Bluesky\BlueskyApi;
use potibm\Bluesky\BlueskyPostService;
use potibm\Bluesky\Feed\Post;
use potibm\Bluesky\HttpComponentsManager;

/**
 * Mastodon web hook connector.
 */
final class Bluesky extends AbstractSocialConnector
{
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->config ?? [];

        $handle = Types::stringOrNull($config['handle'] ?? null, true);
        $appPassword = Types::stringOrNull($config['app_password'] ?? null, true);

        if (null === $handle || null === $appPassword) {
            throw $this->incompleteConfigException($webhook);
        }

        $httpFactory = new HttpFactory();
        $httpComponentsManager = new HttpComponentsManager(
            $this->httpClient,
            $httpFactory,
            $httpFactory,
            $httpFactory
        );

        $api = new BlueskyApi(
            $handle,
            $appPassword,
            $httpComponentsManager
        );
        $postService = new BlueskyPostService($api);

        $this->logger->debug(
            'Posting to Bluesky...',
            [
                'handle' => $handle,
            ]
        );

        foreach ($this->getMessages($webhook, $np, $triggers) as $message) {
            $post = Post::create($message);
            $post = $postService->addFacetsFromMentionsAndLinksAndTags($post);
            $api->createRecord($post);
        }
    }
}
