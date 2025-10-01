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
        $return->id = $record->id;
        $return->title = $record->title;

        $return->link = $record->link;

        $return->description = $record->description;
        $return->description_short = Strings::truncateText($return->description, 100);

        $return->explicit = $podcast->explicit || $record->explicit;
        $return->season_number = $record->season_number;
        $return->episode_number = $record->episode_number;

        $return->created_at = $record->created_at;
        $return->publish_at = $record->publish_at;

        $mediaExtension = '';

        switch ($podcast->source) {
            case PodcastSources::Playlist:
                $return->media = null;

                $playlistMediaRow = $record->playlist_media;
                if ($playlistMediaRow instanceof StationMedia) {
                    $return->has_media = true;

                    $return->playlist_media = $this->songApiGen->__invoke($playlistMediaRow);
                    $return->playlist_media_id = $playlistMediaRow->unique_id;

                    $mediaExtension = Path::getExtension($playlistMediaRow->path);
                } else {
                    $return->has_media = false;

                    $return->playlist_media = null;
                    $return->playlist_media_id = null;
                }
                break;

            case PodcastSources::Manual:
                $return->playlist_media = null;
                $return->playlist_media_id = null;

                $mediaRow = $record->media;
                if ($mediaRow instanceof PodcastMedia) {
                    $media = new ApiPodcastMedia();
                    $media->id = $mediaRow->id;
                    $media->original_name = $mediaRow->original_name;
                    $media->length = $mediaRow->length;
                    $media->length_text = $mediaRow->length_text;
                    $media->path = $mediaRow->path;

                    $return->has_media = true;
                    $return->media = $media;

                    $mediaExtension = Path::getExtension($mediaRow->path);
                } else {
                    $return->has_media = false;
                    $return->media = null;
                }
                break;
        }

        $return->is_published = $record->isPublished();

        $return->art_updated_at = $record->art_updated_at;
        $return->has_custom_art = (0 !== $return->art_updated_at);

        $baseRouteParams = [
            'station_id' => $station->short_name,
            'podcast_id' => $podcast->id,
            'episode_id' => $record->id,
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
        if ($record->podcast->branding_config->enable_op3_prefix) {
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
