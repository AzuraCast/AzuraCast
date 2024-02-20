<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\Api\PodcastMedia as ApiPodcastMedia;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Http\ServerRequest;
use App\Utilities\Strings;

final class PodcastEpisodeApiGenerator
{
    public function __invoke(
        PodcastEpisode $record,
        ServerRequest $request
    ): ApiPodcastEpisode {
        $router = $request->getRouter();
        $isInternal = $request->isInternal();

        $station = $request->getStation();
        $podcast = $request->getPodcast();

        $return = new ApiPodcastEpisode();
        $return->id = $record->getIdRequired();
        $return->title = $record->getTitle();

        $return->description = $record->getDescription();
        $return->description_short = Strings::truncateText($return->description, 100);

        $return->explicit = $record->getExplicit();
        $return->created_at = $record->getCreatedAt();
        $return->publish_at = $record->getPublishAt();

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
        } else {
            $return->has_media = false;
            $return->media = new ApiPodcastMedia();
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

        $return->art = $router->named(
            routeName: 'api:stations:public:podcast:episode:art',
            routeParams: $artRouteParams,
            absolute: !$isInternal
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
            'download' => $router->fromHere(
                routeName: 'api:stations:public:podcast:episode:download',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
        ];

        return $return;
    }
}
