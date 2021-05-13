<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Files;

use App\Entity\Repository\PodcastMediaRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        PodcastMediaRepository $podcastMediaRepository,
        int $podcast_media_id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $podcastMedia = $podcastMediaRepository->fetchPodcastMediaForStation($station, $podcast_media_id);

        $fileMeta = $podcastsFilesystem->getMetadata($podcastMedia->getPath());

        $filename = $podcastMedia->getOriginalName() . '.' . $fileMeta['extension'];

        return $response->streamFilesystemFile(
            $podcastsFilesystem,
            $podcastMedia->getPath(),
            $filename
        );
    }
}
