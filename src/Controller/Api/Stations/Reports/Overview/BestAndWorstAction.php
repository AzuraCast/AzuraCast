<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class BestAndWorstAction
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
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        // Get current analytics level.
        if (!$this->settingsRepo->readSettings()->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        /* Song "Deltas" (Changes in Listener Count) */
        $songPerformanceThreshold = CarbonImmutable::parse('-2 days', $station_tz)->getTimestamp();

        // Get all songs played in timeline.
        $baseQuery = $this->em->createQueryBuilder()
            ->select('sh')
            ->from(Entity\SongHistory::class, 'sh')
            ->where('sh.station = :station')
            ->setParameter('station', $station)
            ->andWhere('sh.timestamp_start >= :timestamp')
            ->setParameter('timestamp', $songPerformanceThreshold)
            ->andWhere('sh.listeners_start IS NOT NULL')
            ->andWhere('sh.timestamp_end != 0')
            ->setMaxResults(5);

        $rawStats = [
            'best' => (clone $baseQuery)->orderBy('sh.delta_total', 'DESC')
                ->getQuery()->getArrayResult(),
            'worst' => (clone $baseQuery)->orderBy('sh.delta_total', 'ASC')
                ->getQuery()->getArrayResult(),
        ];

        $stats = [];
        $baseUrl = $request->getRouter()->getBaseUrl();

        foreach ($rawStats as $category => $rawRows) {
            $stats[$category] = array_map(
                function ($row) use ($station, $baseUrl) {
                    $song = ($this->songApiGenerator)(Entity\Song::createFromArray($row), $station);
                    $song->resolveUrls($baseUrl);

                    return [
                        'song' => $song,
                        'stat_start' => $row['listeners_start'] ?? 0,
                        'stat_end' => $row['listeners_end'] ?? 0,
                        'stat_delta' => $row['delta_total'],
                    ];
                },
                $rawRows
            );
        }

        return $response->withJson($stats);
    }
}
