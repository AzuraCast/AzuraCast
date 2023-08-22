<?php

declare(strict_types=1);

namespace App\Webhook\Connector;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Entity\StationWebhook;

/*
 * https://discordapp.com/developers/docs/resources/webhook#execute-webhook
 *
 * JSON/Form Params
 * content	string	the message contents (up to 2000 characters)	one of content, file, embeds
 * username	string	override the default username of the webhook	false
 * avatar_url	string	override the default avatar of the webhook	false
 * tts	bool	true if this is a TTS message	false
 * file	file contents	the contents of the file being sent	one of content, file, embeds
 * embeds	array of embed objects	embedded rich content	one of content, file, embeds
 *
 * Embed Structure
 * title	string	title of embed
 * type	string	type of embed (always "rich" for webhook embeds)
 * description	string	description of embed
 * url	string	url of embed
 * timestamp	ISO8601 timestamp	timestamp of embed content
 * color	integer	color code of the embed
 * footer	embed footer object	footer information
 * image	embed image object	image information
 * thumbnail	embed thumbnail object	thumbnail information
 * video	embed video object	video information
 * provider	embed provider object	provider information
 * author	embed author object	author information
 * fields	array of embed field objects	fields information
 *
 * Embed Footer Structure
 * text	string	footer text
 * icon_url	string	url of footer icon (only supports http(s) and attachments)
 * proxy_icon_url	string	a proxied url of footer icon
 *
 * Embed Thumbnail Structure
 * url	string	source url of thumbnail (only supports http(s) and attachments)
 * proxy_url	string	a proxied url of the thumbnail
 * height	integer	height of thumbnail
 * width	integer	width of thumbnail
 *
 * Embed Provider Structure
 * name	string	name of provider
 * url	string	url of provider
 *
 * Embed Footer Structure
 * text	string	footer text
 * icon_url	string	url of footer icon (only supports http(s) and attachments)
 * proxy_icon_url	string	a proxied url of footer icon
 *
 * Embed Field Structure
 * name	string	name of the field
 * value	string	value of the field
 * inline	bool	whether or not this field should display inline
 */

final class Discord extends AbstractConnector
{
    /**
     * @inheritDoc
     */
    public function dispatch(
        Station $station,
        StationWebhook $webhook,
        NowPlaying $np,
        array $triggers
    ): void {
        $config = $webhook->getConfig();

        $webhookUrl = $this->getValidUrl($config['webhook_url']);

        if (empty($webhookUrl)) {
            throw $this->incompleteConfigException($webhook);
        }

        $rawVars = [
            'content' => $config['content'] ?? '',
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'url' => $config['url'] ?? '',
            'author' => $config['author'] ?? '',
            'thumbnail' => $config['thumbnail'] ?? '',
            'footer' => $config['footer'] ?? '',
        ];

        $vars = $this->replaceVariables($rawVars, $np);

        // Compose webhook
        $embed = array_filter(
            [
                'title' => $vars['title'] ?? '',
                'description' => $vars['description'] ?? '',
                'url' => $this->getValidUrl($vars['url']) ?? '',
                'color' => 2201331, // #2196f3
            ]
        );

        if (!empty($vars['author'])) {
            $embed['author'] = [
                'name' => $vars['author'],
            ];
        }
        if (!empty($vars['thumbnail']) && $this->getImageUrl($vars['thumbnail'])) {
            $embed['thumbnail'] = [
                'url' => $this->getImageUrl($vars['thumbnail']),
            ];
        }
        if (!empty($vars['footer'])) {
            $embed['footer'] = [
                'text' => $vars['footer'],
            ];
        }

        $webhookBody = [];
        $webhookBody['content'] = $vars['content'] ?? '';

        // Don't include an embed if all relevant fields are empty.
        if (count($embed) > 1) {
            $webhookBody['embeds'] = [$embed];
        }

        // Dispatch webhook
        $this->logger->debug('Dispatching Discord webhook...');

        $response = $this->httpClient->request(
            'POST',
            $webhookUrl,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $webhookBody,
            ]
        );

        $this->logHttpResponse(
            $webhook,
            $response,
            $webhookBody
        );
    }

    /** @noinspection HttpUrlsUsage */
    private function getImageUrl(?string $url = null): ?string
    {
        $url = $this->getValidUrl($url);
        if (null !== $url) {
            return str_replace('http://', 'https://', $url);
        }

        return null;
    }
}
