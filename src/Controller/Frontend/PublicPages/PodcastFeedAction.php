<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Entity\ApiGenerator\PodcastApiGenerator;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\Podcast;
use App\Entity\PodcastCategory;
use App\Entity\PodcastEpisode;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Rss\PodcastNamespaceWriter;
use DateTime;
use MarcW\RssWriter\Extension\Atom\AtomLink;
use MarcW\RssWriter\Extension\Atom\AtomWriter;
use MarcW\RssWriter\Extension\Core\Category as RssCategory;
use MarcW\RssWriter\Extension\Core\Channel as RssChannel;
use MarcW\RssWriter\Extension\Core\CoreWriter;
use MarcW\RssWriter\Extension\Core\Enclosure as RssEnclosure;
use MarcW\RssWriter\Extension\Core\Guid as RssGuid;
use MarcW\RssWriter\Extension\Core\Image as RssImage;
use MarcW\RssWriter\Extension\Core\Item as RssItem;
use MarcW\RssWriter\Extension\Itunes\ItunesChannel;
use MarcW\RssWriter\Extension\Itunes\ItunesItem;
use MarcW\RssWriter\Extension\Itunes\ItunesOwner;
use MarcW\RssWriter\Extension\Itunes\ItunesWriter;
use MarcW\RssWriter\Extension\Slash\Slash;
use MarcW\RssWriter\Extension\Slash\SlashWriter;
use MarcW\RssWriter\Extension\Sy\Sy;
use MarcW\RssWriter\Extension\Sy\SyWriter;
use MarcW\RssWriter\RssWriter;
use Psr\Http\Message\ResponseInterface;

final class PodcastFeedAction implements SingleActionInterface
{
    public function __construct(
        private readonly PodcastApiGenerator $podcastApiGenerator,
        private readonly PodcastEpisodeApiGenerator $episodeApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        $podcast = $request->getPodcast();

        $channel = new RssChannel();
        $channel->setTtl(5);
        $channel->setLastBuildDate(new DateTime());

        // Fetch podcast API feed.
        $podcastApi = $this->podcastApiGenerator->__invoke($podcast, $request);

        $channel->setTitle($podcastApi->title);
        $channel->setDescription($podcastApi->description);
        $channel->setLink($podcastApi->link ?? $podcastApi->links['self']);
        $channel->setLanguage($podcastApi->language);

        $channel->setCategories(
            $podcast->getCategories()->map(
                function (PodcastCategory $podcastCategory) {
                    $rssCategory = new RssCategory();
                    if (null === $podcastCategory->getSubTitle()) {
                        $rssCategory->setTitle($podcastCategory->getTitle());
                    } else {
                        $rssCategory->setTitle($podcastCategory->getSubTitle());
                    }
                    return $rssCategory;
                }
            )->getValues()
        );

        $rssImage = new RssImage();
        $rssImage->setTitle($podcastApi->title);
        $rssImage->setUrl($podcastApi->art);
        if (null !== $podcastApi->link) {
            $rssImage->setLink($podcastApi->link);
        }

        $channel->setImage($rssImage);

        // Iterate through episodes.
        $hasPublishedEpisode = false;
        $hasExplicitEpisode = false;

        /** @var PodcastEpisode $episode */
        foreach ($podcast->getEpisodes() as $episode) {
            if (!$episode->isPublished()) {
                continue;
            }

            $hasPublishedEpisode = true;
            if ($episode->getExplicit()) {
                $hasExplicitEpisode = true;
            }

            $channel->addItem($this->buildItemForEpisode($episode, $request));
        }

        if (!$hasPublishedEpisode) {
            throw NotFoundException::podcast();
        }

        $itunesChannel = new ItunesChannel();
        $itunesChannel->setExplicit($hasExplicitEpisode);
        $itunesChannel->setImage($rssImage->getUrl());
        $itunesChannel->setCategories(
            $podcast->getCategories()->map(
                function (PodcastCategory $podcastCategory) {
                    return (null === $podcastCategory->getSubTitle())
                        ? $podcastCategory->getTitle()
                        : [
                            $podcastCategory->getTitle(),
                            $podcastCategory->getSubTitle(),
                        ];
                }
            )->getValues()
        );

        $itunesChannel->setOwner($this->buildItunesOwner($podcast));
        $itunesChannel->setAuthor($podcast->getAuthor());

        $channel->addExtension($itunesChannel);
        $channel->addExtension(new Sy());
        $channel->addExtension(new Slash());
        $channel->addExtension(
            (new AtomLink())
                ->setRel('self')
                ->setHref((string)$request->getUri())
                ->setType('application/rss+xml')
        );

        $rssWriter = new RssWriter(null, [
            new CoreWriter(),
            new ItunesWriter(),
            new SyWriter(),
            new SlashWriter(),
            new AtomWriter(),
            new PodcastNamespaceWriter(),
        ], true);

        $response->getBody()->write(
            $rssWriter->writeChannel($channel)
        );

        return $response
            ->withHeader('Content-Type', 'application/rss+xml')
            ->withHeader('X-Robots-Tag', 'index, nofollow');
    }

    private function buildItemForEpisode(PodcastEpisode $episode, ServerRequest $request): RssItem
    {
        $episodeApi = $this->episodeApiGenerator->__invoke($episode, $request);

        $rssItem = new RssItem();

        $rssItem->setGuid((new RssGuid())->setGuid($episodeApi->id));
        $rssItem->setTitle($episodeApi->title);
        $rssItem->setDescription($episodeApi->description);
        $rssItem->setLink($episodeApi->link ?? $episodeApi->links['self']);

        $rssItem->setPubDate((new DateTime())->setTimestamp($episode->getPublishAt()));

        $rssEnclosure = new RssEnclosure();
        $rssEnclosure->setUrl($episodeApi->links['download']);

        $podcastMedia = $episode->getMedia();
        if (null !== $podcastMedia) {
            $rssEnclosure->setType($podcastMedia->getMimeType());
            $rssEnclosure->setLength($podcastMedia->getLength());
        }
        $rssItem->setEnclosure($rssEnclosure);

        $rssItem->addExtension(
            (new ItunesItem())
                ->setExplicit($episode->getExplicit())
                ->setImage($episodeApi->art)
        );

        return $rssItem;
    }

    private function buildItunesOwner(Podcast $podcast): ?ItunesOwner
    {
        if (empty($podcast->getAuthor()) && empty($podcast->getEmail())) {
            return null;
        }

        $itunesOwner = new ItunesOwner();
        $itunesOwner->setName($podcast->getAuthor());
        $itunesOwner->setEmail($podcast->getEmail());

        return $itunesOwner;
    }
}
