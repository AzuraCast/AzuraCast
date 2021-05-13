<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Files;

use App\Entity\PodcastMedia;
use App\Entity\Repository\StationRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        StationRepository $stationRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $searchPhrase = trim($request->getParam('searchPhrase', ''));

        $podcastMediaQueryBuilder = $em->createQueryBuilder()
            ->select('pm, s, e')
            ->from(PodcastMedia::class, 'pm')
            ->join('pm.station', 's')
            ->leftJoin('pm.episode', 'e')
            ->where('pm.stationId = :stationId')
            ->setParameter('stationId', $station->getId());

        if (!empty($searchPhrase)) {
            $podcastMediaQueryBuilder->andWhere('(pm.originalName LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        $paginator = Paginator::fromQueryBuilder($podcastMediaQueryBuilder, $request);

        $paginator->setPostprocessor(
            function (PodcastMedia $podcastMedia) use ($station, $podcastsFilesystem, $stationRepo, $router) {
                $podcastMediaMetaData = $podcastsFilesystem->getMetadata(
                    $podcastMedia->getPath()
                );


                $podcastMediaArtSrc = (string)$stationRepo->getDefaultAlbumArtUrl($station);

                $podcastMediaArtworkPath = $podcastMedia->getArtworkPath($podcastMedia->getUniqueId());
                if ($podcastsFilesystem->fileExists($podcastMediaArtworkPath)) {
                    $podcastMediaArtSrc = (string)$router->named(
                        'api:stations:podcast-media:art',
                        [
                            'station_id' => $station->getId(),
                            'podcast_media_id' => $podcastMedia->getUniqueId(),
                        ]
                    );
                }

                $podcastMediaPlayUrl = (string) $router->named(
                    'api:stations:podcast-files:download',
                    [
                        'station_id' => $station->getId(),
                        'podcast_media_id' => $podcastMedia->getId(),
                    ],
                    [],
                    true
                );

                return [
                    'id' => $podcastMedia->getId(),
                    'unique_id' => $podcastMedia->getUniqueId(),
                    'path' => $podcastMedia->getPath(),
                    'length' => $podcastMedia->getLength(),
                    'length_text' => $podcastMedia->getLengthText(),
                    'original_name' => $podcastMedia->getOriginalName(),
                    'art_src' => $podcastMediaArtSrc,
                    'play_url' => $podcastMediaPlayUrl,
                    'size' => $podcastsFilesystem->fileSize($podcastMedia->getPath()),
                    'modified_at' => $podcastMediaMetaData->lastModified(),
                    'is_dir' => false,
                    'links' => [
                        'self' => $router->fromHere(
                            'api:stations:podcast-files:delete',
                            ['podcast_media_id' => $podcastMedia->getId()]
                        ),
                    ],
                ];
            }
        );

        return $paginator->write($response);
    }
}
