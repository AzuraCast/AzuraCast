<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class MostPlayedAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Entity\ApiGenerator\SongApiGenerator $songApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        // Get current analytics level.
        if (!$this->settingsRepo->readSettings()->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $statisticsThreshold = CarbonImmutable::parse('-1 month', $station_tz)
            ->getTimestamp();

        /* Song "Deltas" (Changes in Listener Count) */
        $rawRows = $this->em->createQuery(
            <<<'DQL'
                SELECT sh.song_id, sh.text, sh.artist, sh.title, COUNT(sh.id) AS records
                FROM App\Entity\SongHistory sh
                WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp
                GROUP BY sh.song_id
                ORDER BY records DESC
            DQL
        )->setParameter('station_id', $station->getId())
            ->setParameter('timestamp', $statisticsThreshold)
            ->setMaxResults(10)
            ->getArrayResult();

        $baseUrl = $request->getRouter()->getBaseUrl();

        $stats = array_map(
            function ($row) use ($station, $baseUrl) {
                $song = ($this->songApiGenerator)(Entity\Song::createFromArray($row), $station);
                $song->resolveUrls($baseUrl);

                return [
                    'song' => $song,
                    'num_plays' => $row['records'],
                ];
            },
            $rawRows
        );

        return $response->withJson($stats);
    }
}
