<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\StationWebhook;
use App\Webhook\Enums\WebhookTriggers;
use Generator;

abstract class AbstractSocialConnector extends AbstractConnector
{
    protected function getMessages(
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): Generator {
        $config = $webhook->getConfig();

        $messages = [
            WebhookTriggers::SongChanged->value => $config['message'] ?? '',
            WebhookTriggers::SongChangedLive->value => $config['message_song_changed_live'] ?? '',
            WebhookTriggers::LiveConnect->value => $config['message_live_connect'] ?? '',
            WebhookTriggers::LiveDisconnect->value => $config['message_live_disconnect'] ?? '',
            WebhookTriggers::StationOffline->value => $config['message_station_offline'] ?? '',
            WebhookTriggers::StationOnline->value => $config['message_station_online'] ?? '',
        ];

        foreach ($triggers as $trigger) {
            if (!$webhook->hasTrigger($trigger)) {
                continue;
            }

            $message = $messages[$trigger] ?? '';
            if (empty($message)) {
                continue;
            }

            $vars = $this->replaceVariables(['message' => $message], $np);
            yield $vars['message'];
        }
    }
}
