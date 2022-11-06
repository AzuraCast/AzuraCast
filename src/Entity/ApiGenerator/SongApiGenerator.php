<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity;
use App\Http\Router;
use App\Media\RemoteAlbumArt;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;

final class SongApiGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Router $router,
        private readonly Entity\Repository\StationRepository $stationRepo,
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo,
        private readonly RemoteAlbumArt $remoteAlbumArt
    ) {
    }

    public function __invoke(
        Entity\Interfaces\SongInterface $song,
        ?Entity\Station $station = null,
        ?UriInterface $baseUri = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
    ): Entity\Api\Song {
        $response = new Entity\Api\Song();
        $response->id = $song->getSongId();
        $response->text = $song->getText() ?? '';
        $response->artist = $song->getArtist() ?? '';
        $response->title = $song->getTitle() ?? '';

        if ($song instanceof Entity\StationMedia) {
            $response->album = $song->getAlbum() ?? '';
            $response->genre = $song->getGenre() ?? '';
            $response->isrc = $song->getIsrc() ?? '';
            $response->lyrics = $song->getLyrics() ?? '';

            $response->custom_fields = $this->getCustomFields($song->getId());
        } else {
            $response->custom_fields = $this->getCustomFields();
        }

        $response->art = UriResolver::resolve(
            $baseUri ?? $this->router->getBaseUrl(),
            $this->getAlbumArtUrl($song, $station, $allowRemoteArt, $isNowPlaying)
        );

        return $response;
    }

    private function getAlbumArtUrl(
        Entity\Interfaces\SongInterface $song,
        ?Entity\Station $station = null,
        bool $allowRemoteArt = false,
        bool $isNowPlaying = false,
    ): UriInterface {
        if (null !== $station && $song instanceof Entity\StationMedia) {
            $mediaUpdatedTimestamp = $song->getArtUpdatedAt();
            $mediaId = $song->getUniqueId();
            if (0 !== $mediaUpdatedTimestamp) {
                $mediaId .= '-' . $mediaUpdatedTimestamp;
            }

            return $this->router->namedAsUri(
                routeName: 'api:stations:media:art',
                routeParams: [
                    'station_id' => $station->getId(),
                    'media_id' => $mediaId,
                ]
            );
        }

        if ($allowRemoteArt && $this->remoteAlbumArt->enableForApis()) {
            $url = $this->remoteAlbumArt->getUrlForSong($song);
            if (null !== $url) {
                return Utils::uriFor($url);
            }
        }

        if ($isNowPlaying && null !== $station) {
            $currentStreamer = $station->getCurrentStreamer();
            if (null !== $currentStreamer && 0 !== $currentStreamer->getArtUpdatedAt()) {
                return $this->router->namedAsUri(
                    routeName: 'api:stations:streamer:art',
                    routeParams: [
                        'station_id' => $station->getIdRequired(),
                        'id' => $currentStreamer->getIdRequired() . '|' . $currentStreamer->getArtUpdatedAt(),
                    ],
                );
            }
        }

        return $this->stationRepo->getDefaultAlbumArtUrl($station);
    }

    /**
     * Return all custom fields, either with a null value or with the custom value assigned to the given Media ID.
     *
     * @param int|null $media_id
     *
     * @return mixed[]
     */
    private function getCustomFields(?int $media_id = null): array
    {
        $fields = $this->customFieldRepo->getFieldIds();

        $mediaFields = [];
        if ($media_id !== null) {
            $mediaFieldsRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT smcf.field_id, smcf.value
                    FROM App\Entity\StationMediaCustomField smcf
                    WHERE smcf.media_id = :media_id
                DQL
            )->setParameter('media_id', $media_id)
                ->getArrayResult();

            foreach ($mediaFieldsRaw as $row) {
                $mediaFields[$row['field_id']] = $row['value'];
            }
        }

        $customFields = [];
        foreach ($fields as $fieldId => $fieldKey) {
            $customFields[$fieldKey] = $mediaFields[$fieldId] ?? null;
        }
        return $customFields;
    }
}
