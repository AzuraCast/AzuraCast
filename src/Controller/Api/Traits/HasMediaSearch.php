<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Station;
use App\Entity\StationPlaylist;

trait HasMediaSearch
{
    use EntityManagerAwareTrait;

    private function parseSearchQuery(
        Station $station,
        string $query
    ): array {
        $special = null;
        $playlist = null;

        if (str_contains($query, 'special:')) {
            preg_match('/special:(\S*)/', $query, $matches, PREG_UNMATCHED_AS_NULL);
            if ($matches[1]) {
                $special = $matches[1];
            }

            $query = trim(str_replace($matches[0] ?? '', '', $query));
        }

        if (str_contains($query, 'playlist:')) {
            preg_match('/playlist:(\S*)/', $query, $matches, PREG_UNMATCHED_AS_NULL);

            if ($matches[1]) {
                $playlistId = $matches[1];

                if (!is_numeric($playlistId)) {
                    $playlistNameLookupRaw = $this->em->createQuery(
                        <<<'DQL'
                        SELECT sp.id, sp.name
                        FROM App\Entity\StationPlaylist sp
                        WHERE sp.station = :station
                        DQL
                    )->setParameter('station', $station)
                        ->getArrayResult();

                    foreach ($playlistNameLookupRaw as $playlistRow) {
                        $shortName = StationPlaylist::generateShortName($playlistRow['name']);
                        if ($shortName === $playlistId) {
                            $playlistId = $playlistRow['id'];
                            break;
                        }
                    }
                }

                $playlist = $this->em->getRepository(StationPlaylist::class)
                    ->findOneBy(
                        [
                            'station' => $station,
                            'id' => $playlistId,
                        ]
                    );
            }

            $query = trim(str_replace($matches[0] ?? '', '', $query));
        }

        if (in_array($query, ['*', '%'], true)) {
            $query = '';
        }

        return [
            $query,
            $playlist,
            $special,
        ];
    }
}
