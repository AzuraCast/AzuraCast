<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\Podcast as ApiPodcast;
use App\Entity\Api\PodcastCategory as ApiPodcastCategory;
use App\Entity\Podcast;
use App\Entity\Repository\PodcastRepository;
use App\Entity\Station;
use App\Http\ServerRequest;
use App\Utilities\Strings;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Uid\Uuid;

final class PodcastApiGenerator
{
    public const string PODCAST_GUID_NAMESPACE = 'ead4c236-bf58-58c6-a2c6-a6b28d128cb6';

    /**
     * @var array<string, array<string>>
     */
    private array $publishedPodcasts = [];

    public function __construct(
        private readonly PodcastRepository $podcastRepo
    ) {
    }

    public function __invoke(
        Podcast $record,
        ServerRequest $request
    ): ApiPodcast {
        $router = $request->getRouter();
        $isInternal = $request->isInternal();
        $station = $request->getStation();

        $return = new ApiPodcast();
        $return->id = $record->getIdRequired();
        $return->storage_location_id = $record->getStorageLocation()->getIdRequired();

        $return->source = $record->getSource()->value;
        $return->playlist_id = $record->getPlaylist()?->getIdRequired();
        $return->playlist_auto_publish = $record->playlistAutoPublish();

        $return->title = $record->getTitle();
        $return->link = $record->getLink();

        $return->description = $record->getDescription();
        $return->description_short = Strings::truncateText($return->description, 200);

        $return->is_enabled = $record->isEnabled();

        $return->branding_config = $record->getBrandingConfig();

        $return->language = $record->getLanguage();
        try {
            $locale = $request->getCustomization()->getLocale();
            $return->language_name = Languages::getName(
                $return->language,
                $locale->value
            );
        } catch (MissingResourceException) {
        }

        $return->author = $record->getAuthor();
        $return->email = $record->getEmail();

        $categories = [];
        foreach ($record->getCategories() as $category) {
            $categoryRow = new ApiPodcastCategory();
            $categoryRow->category = $category->getCategory();
            $categoryRow->title = $category->getTitle();
            $categoryRow->subtitle = $category->getSubTitle();

            $categoryRow->text = (!empty($categoryRow->subtitle))
                ? $categoryRow->title . ' - ' . $categoryRow->subtitle
                : $categoryRow->title;

            $categories[] = $categoryRow;
        }
        $return->categories = $categories;

        $return->is_published = $this->isPublished($record, $station);

        $return->art_updated_at = $record->getArtUpdatedAt();
        $return->has_custom_art = (0 !== $record->getArtUpdatedAt());

        $return->episodes = $record->getEpisodes()->count();

        $baseRouteParams = [
            'station_id' => $station->getIdRequired(),
            'podcast_id' => $record->getIdRequired(),
        ];

        $artRouteParams = $baseRouteParams;
        if ($return->has_custom_art) {
            $artRouteParams['timestamp'] = $record->getArtUpdatedAt();
        }

        $return->art = $router->named(
            routeName: 'api:stations:public:podcast:art',
            routeParams: $artRouteParams,
            absolute: !$isInternal
        );

        $feedUri = $router->namedAsUri(
            routeName: 'public:podcast:feed',
            routeParams: $baseRouteParams,
            absolute: true
        );

        $return->guid = $this->buildPodcastGuid($feedUri);

        $return->links = [
            'self' => $router->named(
                routeName: 'api:stations:public:podcast',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'episodes' => $router->named(
                routeName: 'api:stations:public:podcast:episodes',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'public_episodes' => $router->named(
                routeName: 'public:podcast',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'public_feed' => (string)$feedUri,
        ];

        return $return;
    }

    private function isPublished(
        Podcast $podcast,
        Station $station
    ): bool {
        if (!isset($this->publishedPodcasts[$station->getShortName()])) {
            $this->publishedPodcasts[$station->getShortName()] = $this->podcastRepo->getPodcastIdsWithPublishedEpisodes(
                $station
            );
        }

        return in_array(
            $podcast->getIdRequired(),
            $this->publishedPodcasts[$station->getShortName()] ?? [],
            true
        );
    }

    private function buildPodcastGuid(UriInterface $uri): string
    {
        $baseUri = rtrim(
            (string)$uri->withScheme(''),
            '/'
        );

        return (string)Uuid::v5(
            Uuid::fromString(self::PODCAST_GUID_NAMESPACE),
            $baseUri
        );
    }
}
