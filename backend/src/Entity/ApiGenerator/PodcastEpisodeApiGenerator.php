<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\Podcast;
use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\Api\PodcastMedia as ApiPodcastMedia;
use App\Entity\Enums\PodcastSources;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\StationMedia;
use App\Http\ServerRequest;
use App\Utilities\Strings;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;

final class PodcastEpisodeApiGenerator
{
    public const string OP3_BASE_URL = 'https://op3.dev/e';

    public function __construct(
        private readonly SongApiGenerator $songApiGen
    ) {
    }

    public function __invoke(
        PodcastEpisode $record,
        ServerRequest $request,
        ?Podcast $apiPodcast = null
    ): ApiPodcastEpisode {
        $router = $request->getRouter();
        $isInternal = $request->isInternal();

        $station = $request->getStation();
        $podcast = $request->getPodcast();

        $return = new ApiPodcastEpisode();
        $return->id = $record->getIdRequired();
        $return->title = $record->getTitle();

        $return->link = $record->getLink();

        $return->description = $record->getDescription();
        $return->description_short = Strings::truncateText($return->description, 100);

        $return->explicit = $record->getExplicit();
        $return->season_number = $record->getSeasonNumber();
        $return->episode_number = $record->getEpisodeNumber();

        $return->created_at = $record->getCreatedAt();
        $return->publish_at = $record->getPublishAt();

        $mediaExtension = '';

        switch ($podcast->getSource()) {
            case PodcastSources::Playlist:
                $return->media = null;

                $playlistMediaRow = $record->getPlaylistMedia();
                if ($playlistMediaRow instanceof StationMedia) {
                    $return->has_media = true;

                    $return->playlist_media = $this->songApiGen->__invoke($playlistMediaRow);
                    $return->playlist_media_id = $playlistMediaRow->getUniqueId();

                    $mediaExtension = Path::getExtension($playlistMediaRow->getPath());
                } else {
                    $return->has_media = false;

                    $return->playlist_media = null;
                    $return->playlist_media_id = null;
                }
                break;

            case PodcastSources::Manual:
                $return->playlist_media = null;
                $return->playlist_media_id = null;

                $mediaRow = $record->getMedia();
                $return->has_media = ($mediaRow instanceof PodcastMedia);
                if ($mediaRow instanceof PodcastMedia) {
                    $media = new ApiPodcastMedia();
                    $media->id = $mediaRow->getId();
                    $media->original_name = $mediaRow->getOriginalName();
                    $media->length = $mediaRow->getLength();
                    $media->length_text = $mediaRow->getLengthText();
                    $media->path = $mediaRow->getPath();

                    $return->has_media = true;
                    $return->media = $media;

                    $mediaExtension = Path::getExtension($mediaRow->getPath());
                } else {
                    $return->has_media = false;
                    $return->media = null;
                }
                break;
        }

        $return->is_published = $record->isPublished();

        $return->art_updated_at = $record->getArtUpdatedAt();
        $return->has_custom_art = (0 !== $return->art_updated_at);

        $baseRouteParams = [
            'station_id' => $station->getShortName(),
            'podcast_id' => $podcast->getIdRequired(),
            'episode_id' => $record->getIdRequired(),
        ];

        $artRouteParams = $baseRouteParams;
        if (0 !== $return->art_updated_at) {
            $artRouteParams['timestamp'] = $return->art_updated_at;
        }

        $downloadRouteParams = $baseRouteParams;
        if ('' !== $mediaExtension) {
            $downloadRouteParams['extension'] = $mediaExtension;
        }

        $return->art = $router->named(
            routeName: 'api:stations:public:podcast:episode:art',
            routeParams: $artRouteParams,
            absolute: !$isInternal
        );

        $downloadUri = $router->fromHereAsUri(
            routeName: 'api:stations:public:podcast:episode:download',
            routeParams: $downloadRouteParams,
            absolute: true
        );

        $return->links = [
            'self' => $router->named(
                routeName: 'api:stations:public:podcast:episode',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'public' => $router->fromHere(
                routeName: 'public:podcast:episode',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'download' => (string)$this->buildDownloadUri($record, $downloadUri, $apiPodcast),
        ];

        return $return;
    }

    private function buildDownloadUri(
        PodcastEpisode $record,
        UriInterface $downloadUri,
        ?Podcast $apiPodcast = null
    ): UriInterface {
        if ($record->getPodcast()->getBrandingConfig()->enable_op3_prefix) {
            $prefixUri = new Uri(self::OP3_BASE_URL);

            $baseUri = ($downloadUri->getScheme() === 'http')
                ? (string)$downloadUri
                : (string)$downloadUri->withScheme('');
            $baseUri = ltrim($baseUri, '/');

            $podcastGuid = $apiPodcast?->guid;
            return ($podcastGuid !== null)
                ? $prefixUri->withPath($prefixUri->getPath() . ',pg=' . $podcastGuid . '/' . $baseUri)
                : $prefixUri->withPath($prefixUri->getPath() . '/' . $baseUri);
        }

        return $downloadUri;
    }
}
