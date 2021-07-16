<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class MostPlayedAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\ApiGenerator\SongApiGenerator $songApiGenerator
    ): ResponseInterface {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        // Get current analytics level.
        $analytics_level = $settingsRepo->readSettings()->getAnalytics();

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $statisticsThreshold = CarbonImmutable::parse('-1 month', $station_tz)
            ->getTimestamp();

        /* Song "Deltas" (Changes in Listener Count) */
        $rawRows = $em->createQuery(
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
            static function ($row) use ($songApiGenerator, $station, $baseUrl) {
                $song = ($songApiGenerator)(Entity\Song::createFromArray($row), $station);
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
