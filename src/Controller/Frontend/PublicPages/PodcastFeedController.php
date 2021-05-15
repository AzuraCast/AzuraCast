<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Entity\Podcast;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Exception\PodcastNotFoundException;
use App\Exception\StationNotFoundException;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use GuzzleHttp\Psr7\UriResolver;
use MarcW\RssWriter\Extension\Atom\AtomLink;
use MarcW\RssWriter\Extension\Atom\AtomWriter;
use MarcW\RssWriter\Extension\Core\Category as RssCategory;
use MarcW\RssWriter\Extension\Core\Channel as RssChannel;
use MarcW\RssWriter\Extension\Core\CoreWriter;
use MarcW\RssWriter\Extension\Core\Enclosure as RssEnclosure;
use MarcW\RssWriter\Extension\Core\Guid as RssGuid;
use MarcW\RssWriter\Extension\Core\Image as RssImage;
use MarcW\RssWriter\Extension\Core\Item as RssItem;
use MarcW\RssWriter\Extension\DublinCore\DublinCore;
use MarcW\RssWriter\Extension\DublinCore\DublinCoreWriter;
use MarcW\RssWriter\Extension\Itunes\ItunesChannel;
use MarcW\RssWriter\Extension\Itunes\ItunesItem;
use MarcW\RssWriter\Extension\Itunes\ItunesWriter;
use MarcW\RssWriter\Extension\Slash\Slash;
use MarcW\RssWriter\Extension\Slash\SlashWriter;
use MarcW\RssWriter\Extension\Sy\Sy;
use MarcW\RssWriter\Extension\Sy\SyWriter;
use MarcW\RssWriter\RssWriter;
use Psr\Http\Message\ResponseInterface;

class PodcastFeedController
{
    protected RouterInterface $router;

    public function __construct(
        protected StationRepository $stationRepository,
        protected PodcastRepository $podcastRepository
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $podcast_id,
    ): ResponseInterface {
        $this->router = $request->getRouter();

        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        if ($podcast === null) {
            throw new PodcastNotFoundException();
        }

        if (!$this->checkHasPublishedEpisodes($podcast)) {
            throw new PodcastNotFoundException();
        }

        $generatedRss = $this->generateRssFeed($podcast, $station, $request);

        $response->getBody()->write($generatedRss);

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }

    protected function checkHasPublishedEpisodes(Podcast $podcast): bool
    {
        /** @var PodcastEpisode $episode */
        foreach ($podcast->getEpisodes() as $episode) {
            if ($episode->isPublished()) {
                return true;
            }
        }

        return false;
    }

    protected function generateRssFeed(
        Podcast $podcast,
        Station $station,
        ServerRequest $serverRequest
    ): string {
        $rssWriter = $this->createRssWriter();

        $channel = $this->buildRssChannelForPodcast($podcast, $station, $serverRequest);

        return $rssWriter->writeChannel($channel);
    }

    protected function createRssWriter(): RssWriter
    {
        $rssWriter = new RssWriter(null, [], true);

        $rssWriter->registerWriter(new CoreWriter());
        $rssWriter->registerWriter(new ItunesWriter());
        $rssWriter->registerWriter(new SyWriter());
        $rssWriter->registerWriter(new SlashWriter());
        $rssWriter->registerWriter(new AtomWriter());
        $rssWriter->registerWriter(new DublinCoreWriter());

        return $rssWriter;
    }

    protected function buildRssChannelForPodcast(
        Podcast $podcast,
        Station $station,
        ServerRequest $serverRequest
    ): RssChannel {
        $channel = new RssChannel();

        $channel->setTtl(5);
        $channel->setLastBuildDate(new \DateTime());

        $channel->setTitle($podcast->getTitle());
        $channel->setDescription($podcast->getDescription());
        $channel->setLink($podcast->getLink());
        $channel->setLanguage($podcast->getLanguage());

        $categories = $this->buildRssCategoriesForPodcast($podcast);
        $channel->setCategories($categories);

        $rssImage = $this->buildRssImageForPodcast($podcast, $station);
        $channel->setImage($rssImage);

        $rssItems = $this->buildRssItemsForPodcast($podcast, $station);
        $channel->setItems($rssItems);

        $containsExplicitContent = $this->rssItemsContainsExplicitContent($rssItems);

        $itunesChannel = new ItunesChannel();
        $itunesChannel->setExplicit($containsExplicitContent);

        foreach ($categories as $category) {
            $itunesChannel->addCategory($category->getTitle());
        }

        $channel->addExtension($itunesChannel);
        $channel->addExtension(new Sy());
        $channel->addExtension(new Slash());
        $channel->addExtension(
            (new AtomLink())
                ->setRel('self')
                ->setHref((string)$serverRequest->getUri())
                ->setType('application/rss+xml')
        );
        $channel->addExtension(new DublinCore());

        return $channel;
    }

    /**
     * @return RssCategory[]
     */
    protected function buildRssCategoriesForPodcast(Podcast $podcast): array
    {
        $categories = [];

        foreach ($podcast->getCategories() as $podcastCategory) {
            $rssCategory = new RssCategory();
            if (null === $podcastCategory->getSubTitle()) {
                $rssCategory->setTitle($podcastCategory->getTitle());
            } else {
                $rssCategory->setTitle($podcastCategory->getSubTitle());
            }

            $categories[] = $rssCategory;
        }

        return $categories;
    }

    protected function buildRssImageForPodcast(Podcast $podcast, Station $station): RssImage
    {
        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $rssImage = new RssImage();

        $podcastArtworkSrc = (string)UriResolver::resolve(
            $this->router->getBaseUrl(),
            $this->stationRepository->getDefaultAlbumArtUrl($station)
        );

        if ($podcastsFilesystem->fileExists(Podcast::getArtPath($podcast->getId()))) {
            $podcastArtworkSrc = (string)$this->router->named(
                'api:stations:podcast:art',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $podcast->getId(),
                ],
                [],
                true
            );
        }

        $rssImage->setUrl($podcastArtworkSrc);
        $rssImage->setLink($podcast->getLink());
        $rssImage->setTitle($podcast->getTitle());

        return $rssImage;
    }

    /**
     * @return RssItem[]
     */
    protected function buildRssItemsForPodcast(Podcast $podcast, Station $station): array
    {
        $rssItems = [];

        /** @var PodcastEpisode $episode */
        foreach ($podcast->getEpisodes() as $episode) {
            if (!$episode->isPublished()) {
                continue;
            }

            $rssItem = new RssItem();

            $rssGuid = new RssGuid();
            $rssGuid->setGuid($episode->getId());

            $rssItem->setGuid($rssGuid);
            $rssItem->setTitle($episode->getTitle());
            $rssItem->setDescription($episode->getDescription());
            $rssItem->setLink($episode->getLink());

            $publishAtDateTime = (new \DateTime())->setTimestamp($episode->getCreatedAt());

            if ($episode->getPublishAt() !== null) {
                $publishAtDateTime = (new \DateTime())->setTimestamp($episode->getPublishAt());
            }

            $rssItem->setPubDate($publishAtDateTime);

            $rssEnclosure = $this->buildRssEnclosureForPodcastMedia(
                $episode->getMedia(),
                $station
            );
            $rssItem->setEnclosure($rssEnclosure);

            $itunesImage = $this->buildItunesImageForEpisode($episode, $station);
            $rssItem->addExtension(
                (new ItunesItem())
                    ->setExplicit($episode->getExplicit())
                    ->setImage($itunesImage)
            );

            $rssItems[] = $rssItem;
        }

        return $rssItems;
    }

    protected function buildRssEnclosureForPodcastMedia(
        PodcastMedia $podcastMedia,
        Station $station
    ): RssEnclosure {
        $rssEnclosure = new RssEnclosure();

        $podcastMediaPlayUrl = (string) $this->router->named(
            'api:stations:podcast-files:download',
            [
                'station_id' => $station->getId(),
                'podcast_media_id' => $podcastMedia->getId(),
            ],
            [],
            true
        );

        $rssEnclosure->setUrl($podcastMediaPlayUrl);
        $rssEnclosure->setType($podcastMedia->getMimeType());
        $rssEnclosure->setLength($podcastMedia->getLength());

        return $rssEnclosure;
    }

    protected function buildItunesImageForEpisode(PodcastEpisode $episode, Station $station): string
    {
        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $episodeArtworkSrc = (string)UriResolver::resolve(
            $this->router->getBaseUrl(),
            $this->stationRepository->getDefaultAlbumArtUrl($station)
        );

        if ($podcastsFilesystem->fileExists(PodcastEpisode::getArtPath($episode->getId()))) {
            $episodeArtworkSrc = (string)$this->router->named(
                'api:stations:episode:art',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $episode->getPodcast()->getId(),
                    'episode_id' => $episode->getId(),
                ],
                [],
                true
            );
        }

        return $episodeArtworkSrc;
    }

    /**
     * @param RssItem[] $rssItems
     */
    protected function rssItemsContainsExplicitContent(array $rssItems): bool
    {
        foreach ($rssItems as $rssItem) {
            foreach ($rssItem->getExtensions() as $extension) {
                if (($extension instanceof ItunesItem) === false) {
                    continue;
                }

                /** @var ItunesItem $extension */
                if ($extension->getExplicit()) {
                    return true;
                }
            }
        }

        return false;
    }
}
