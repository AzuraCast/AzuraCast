<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\Song;
use App\Entity\SongHistory;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\DateRange;
use Psr\Http\Message\ResponseInterface;

final class BestAndWorstAction extends AbstractReportAction
{
    public function __construct(
        private readonly SongApiGenerator $songApiGenerator,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Get current analytics level.
        if (!$this->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $dateRange = $this->getDateRange($request, $request->getStation()->getTimezoneObject());

        return $response->withJson([
            'bestAndWorst' => $this->getBestAndWorst($request, $dateRange),
            'mostPlayed' => $this->getMostPlayed($request, $dateRange),
        ]);
    }

    private function getBestAndWorst(
        ServerRequest $request,
        DateRange $dateRange
    ): array {
        $station = $request->getStation();

        // Get all songs played in timeline.
        $baseQuery = $this->em->createQueryBuilder()
            ->select('sh')
            ->from(SongHistory::class, 'sh')
            ->where('sh.station = :station')
            ->setParameter('station', $station)
            ->andWhere('sh.timestamp_start <= :end AND sh.timestamp_end >= :start')
            ->setParameter('start', $dateRange->getStartTimestamp())
            ->setParameter('end', $dateRange->getEndTimestamp())
            ->andWhere('sh.is_visible = 1')
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
                    $song = ($this->songApiGenerator)(Song::createFromArray($row), $station);
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

        return $stats;
    }

    private function getMostPlayed(
        ServerRequest $request,
        DateRange $dateRange
    ): array {
        $station = $request->getStation();

        $rawRows = $this->em->createQuery(
            <<<'DQL'
                SELECT sh.song_id, sh.text, sh.artist, sh.title, COUNT(sh.id) AS records
                FROM App\Entity\SongHistory sh
                WHERE sh.station = :station 
                AND sh.is_visible = 1
                AND sh.timestamp_start <= :end
                AND sh.timestamp_end >= :start
                GROUP BY sh.song_id
                ORDER BY records DESC
            DQL
        )->setParameter('station', $request->getStation())
            ->setParameter('start', $dateRange->getStartTimestamp())
            ->setParameter('end', $dateRange->getEndTimestamp())
            ->setMaxResults(10)
            ->getArrayResult();

        $baseUrl = $request->getRouter()->getBaseUrl();

        return array_map(
            function ($row) use ($station, $baseUrl) {
                $song = ($this->songApiGenerator)(Song::createFromArray($row), $station);
                $song->resolveUrls($baseUrl);

                return [
                    'song' => $song,
                    'num_plays' => $row['records'],
                ];
            },
            $rawRows
        );
    }
}
