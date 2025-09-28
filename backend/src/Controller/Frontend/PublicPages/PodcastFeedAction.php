<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Podcast;
use App\Entity\ApiGenerator\PodcastApiGenerator;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\PodcastCategory;
use App\Entity\PodcastEpisode;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Xml\Writer;
use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;

final readonly class PodcastFeedAction implements SingleActionInterface
{
    public function __construct(
        private PodcastApiGenerator $podcastApiGenerator,
        private PodcastEpisodeApiGenerator $episodeApiGenerator
    ) {
    }

    /** @noinspection HttpUrlsUsage */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        if (!$station->enable_public_page) {
            throw NotFoundException::station();
        }

        $podcast = $request->getPodcast();

        // Fetch podcast API feed.
        $podcastApi = $this->podcastApiGenerator->__invoke($podcast, $request);

        $now = CarbonImmutable::now($station->getTimezoneObject());

        $rss = [
            '@xmlns:itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
            '@xmlns:sy' => 'http://purl.org/rss/1.0/modules/syndication/',
            '@xmlns:slash' => 'http://purl.org/rss/1.0/modules/slash/',
            '@xmlns:atom' => 'http://www.w3.org/2005/Atom',
            '@xmlns:podcast' => 'https://podcastindex.org/namespace/1.0',
            '@version' => '2.0',
        ];

        $channel = [
            'title' => $podcastApi->title,
            'link' => $podcastApi->link ?? $podcastApi->links['public_episodes'],
            'description' => $podcastApi->description,
            'language' => $podcastApi->language,
            'lastBuildDate' => $now->toRssString(),
            'category' => $podcast->categories->map(
                function (PodcastCategory $podcastCategory) {
                    return (null === $podcastCategory->subtitle)
                        ? $podcastCategory->title
                        : $podcastCategory->subtitle;
                }
            )->getValues(),
            'ttl' => 5,
            'image' => [
                'url' => $podcastApi->art,
                'title' => $podcastApi->title,
            ],
            'itunes:author' => $podcastApi->author,
            'itunes:owner' => [],
            'itunes:image' => [
                '@href' => $podcastApi->art,
            ],
            'itunes:explicit' => 'false',
            'itunes:category' => $podcast->categories->map(
                function (PodcastCategory $podcastCategory) {
                    return (null === $podcastCategory->subtitle)
                        ? [
                            '@text' => $podcastCategory->title,
                        ] : [
                            '@text' => $podcastCategory->title,
                            'itunes:category' => [
                                '@text' => $podcastCategory->subtitle,
                            ],
                        ];
                }
            )->getValues(),
            'atom:link' => [
                '@rel' => 'self',
                '@type' => 'application/rss+xml',
                '@href' => (string)$request->getUri(),
            ],
            'podcast:guid' => $podcastApi->guid,
            'item' => [],
        ];

        if (null !== $podcastApi->link) {
            $channel['image']['link'] = $podcastApi->link;
        }

        if (empty($podcastApi->author) && empty($podcastApi->email)) {
            unset($channel['itunes:owner']);
        } else {
            $channel['itunes:owner'] = [
                'itunes:name' => $podcastApi->author,
                'itunes:email' => $podcastApi->email,
            ];
        }

        // Iterate through episodes.
        $hasPublishedEpisode = false;
        $hasExplicitEpisode = false;

        /** @var PodcastEpisode $episode */
        foreach ($podcast->episodes as $episode) {
            if (!$episode->isPublished()) {
                continue;
            }

            $hasPublishedEpisode = true;
            if ($episode->explicit) {
                $hasExplicitEpisode = true;
            }

            $channel['item'][] = $this->buildItemForEpisode($episode, $request, $podcastApi);
        }

        if (!$hasPublishedEpisode) {
            throw NotFoundException::podcast();
        }

        if ($podcast->explicit || $hasExplicitEpisode) {
            $channel['itunes:explicit'] = 'true';
        }

        $rss['channel'] = $channel;

        $response->getBody()->write(
            Writer::toString($rss, 'rss')
        );

        return $response
            ->withHeader('Content-Type', 'application/rss+xml')
            ->withHeader('X-Robots-Tag', 'index, nofollow');
    }

    private function buildItemForEpisode(
        PodcastEpisode $episode,
        ServerRequest $request,
        Podcast $apiPodcast
    ): array {
        $station = $request->getStation();

        $episodeApi = $this->episodeApiGenerator->__invoke($episode, $request, $apiPodcast);

        $publishedAt = CarbonImmutable::createFromTimestamp($episodeApi->publish_at, $station->getTimezoneObject());

        $item = [
            'title' => $episodeApi->title,
            'link' => $episodeApi->link ?? $episodeApi->links['public'],
            'description' => $episodeApi->description,
            'enclosure' => [
                '@url' => $episodeApi->links['download'],
            ],
            'guid' => [
                '@isPermaLink' => 'false',
                '_' => $episodeApi->id,
            ],
            'pubDate' => $publishedAt->toRssString(),
            'itunes:image' => [
                '@href' => $episodeApi->art,
            ],
            'itunes:explicit' => $episodeApi->explicit ? 'true' : 'false',
        ];

        $podcastMedia = $episode->media;
        if (null !== $podcastMedia) {
            $item['enclosure']['@length'] = $podcastMedia->length;
            $item['enclosure']['@type'] = $podcastMedia->mime_type;
        }

        if (null !== $episodeApi->season_number) {
            $item['itunes:season'] = (string)$episodeApi->season_number;
        }
        if (null !== $episodeApi->episode_number) {
            $item['itunes:episode'] = (string)$episodeApi->episode_number;
        }

        return $item;
    }
}
